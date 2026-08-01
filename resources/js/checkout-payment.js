import { mountApplePay, mountTapCard, unmountPayment } from './checkout-payment-ui';

document.addEventListener('alpine:init', () => {
    Alpine.data('checkoutPayment', (config) => ({
        method: config.defaultMethod || 'knet',
        token: '',
        error: '',
        loading: false,
        cardMount: null,
        appleMount: null,
        config,

        init() {
            if (! this.isMethodEnabled(this.method)) {
                const fallback = ['knet', 'card', 'apple_pay', 'cod'].find((m) => this.isMethodEnabled(m));
                if (fallback) {
                    this.method = fallback;
                }
            }

            this.$watch('method', (value) => {
                this.error = '';
                this.token = '';

                if (value !== 'card') {
                    this.destroyCard();
                }

                if (value !== 'apple_pay') {
                    this.destroyApplePay();
                }

                if (value === 'card' && this.config.cardEnabled) {
                    this.$nextTick(() => this.setupCard());
                }

                if (value === 'apple_pay' && this.config.applePayEnabled) {
                    this.$nextTick(() => this.setupApplePay());
                }
            });
        },

        selectMethod(method) {
            if (!this.isMethodEnabled(method)) {
                return;
            }

            this.method = method;
        },

        isMethodEnabled(method) {
            if (method === 'knet') {
                return this.config.knetEnabled;
            }

            if (method === 'card') {
                return this.config.cardEnabled;
            }

            if (method === 'apple_pay') {
                return this.config.applePayEnabled;
            }

            if (method === 'cod') {
                return this.config.codEnabled;
            }

            return false;
        },

        destroyCard() {
            const el = this.$refs.cardMount;
            if (el) {
                unmountPayment(el);
            }
            this.cardMount = null;
        },

        destroyApplePay() {
            const el = this.$refs.applePayMount;
            if (el) {
                unmountPayment(el);
            }
            this.appleMount = null;
        },

        setupCard() {
            const el = this.$refs.cardMount;
            if (!el || !this.config.cardEnabled) {
                return;
            }

            this.destroyCard();
            this.cardMount = mountTapCard(el, this.buildSdkConfig(), {
                onToken: (tokenId) => {
                    this.token = tokenId;
                    this.error = '';
                    this.submitForm();
                },
                onError: (err) => {
                    this.error = this.normalizeError(err);
                    this.loading = false;
                },
            });
        },

        setupApplePay() {
            const el = this.$refs.applePayMount;
            if (!el || !this.config.applePayEnabled) {
                return;
            }

            this.destroyApplePay();
            this.appleMount = mountApplePay(el, this.buildSdkConfig(), {
                onToken: async (tokenId) => {
                    this.method = 'apple_pay';
                    this.token = tokenId;
                    this.error = '';
                    this.loading = true;
                    this.submitForm();
                },
                onError: (err) => {
                    this.error = this.normalizeError(err);
                    this.loading = false;
                },
            });
        },

        buildSdkConfig() {
            return {
                publicKey: this.config.publicKey,
                merchantId: this.config.merchantId,
                amount: this.config.amount,
                currency: this.config.currency,
                locale: this.config.locale,
                liveMode: this.config.liveMode,
                domain: this.config.domain,
                customer: this.config.customer,
            };
        },

        normalizeError(err) {
            if (typeof err === 'string') {
                return err;
            }

            if (err instanceof Error) {
                return err.message;
            }

            if (err && typeof err === 'object') {
                const message = err.description || err.message;
                if (typeof message === 'string' && message.length > 0) {
                    return message;
                }
            }

            return this.config.errorGeneric;
        },

        submitForm() {
            const form = document.getElementById('checkout-form');
            if (!form) {
                this.loading = false;
                return;
            }

            const methodInput = form.querySelector('[name="payment_method"]');
            const tokenInput = form.querySelector('[name="payment_token"]');

            if (methodInput) {
                methodInput.value = this.method;
            }

            if (tokenInput) {
                tokenInput.value = this.token;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        },

        pay() {
            this.error = '';
            this.loading = true;

            if (this.method === 'knet' || this.method === 'cod') {
                this.token = '';
                this.submitForm();
                return;
            }

            if (this.method === 'card') {
                if (!this.cardMount) {
                    this.error = this.config.errorCardUnavailable;
                    this.loading = false;
                    return;
                }

                this.cardMount.tokenize();
                return;
            }

            this.loading = false;
        },
    }));
});
