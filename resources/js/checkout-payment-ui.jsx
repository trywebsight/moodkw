import { forwardRef, useEffect, useImperativeHandle, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { TapCard, tokenize } from '@tap-payments/card-web';

function extractTokenId(data) {
    if (!data || typeof data !== 'object') {
        if (typeof data === 'string' && data.startsWith('tok_')) {
            return data;
        }

        return null;
    }

    const candidates = [
        data.id,
        data.token,
        data?.source?.id,
        data?.token?.id,
        data?.data?.id,
        data?.data?.source?.id,
        data?.data?.token?.id,
    ];

    for (const candidate of candidates) {
        if (typeof candidate === 'string' && candidate.startsWith('tok_')) {
            return candidate;
        }
    }

    return null;
}

const TapCardMount = forwardRef(function TapCardMount({ config, onToken, onError }, ref) {
    const [isValid, setIsValid] = useState(false);
    const [threeDsUrl, setThreeDsUrl] = useState('');
    const pendingRef = useRef(null);

    const cardConfig = useMemo(() => ({
        operator: { publicKey: config.publicKey },
        scope: 'AuthenticatedToken',
        merchant: { id: config.merchantId },
        order: {
            amount: config.amount,
            currency: config.currency,
            id: `MOOD-${Date.now()}`,
            description: 'MOOD checkout',
        },
        transaction: {
            amount: config.amount,
            currency: config.currency,
        },
        height3DS: 480,
        customer: {
            name: [{
                lang: config.locale === 'ar' ? 'ar' : 'en',
                first: config.customer.firstName,
                last: config.customer.lastName || '',
                middle: '',
            }],
            contact: {
                email: config.customer.email || 'guest@moodkw.com',
                phone: {
                    countryCode: config.customer.phoneCountryCode || '965',
                    number: config.customer.phoneNumber || '',
                },
            },
        },
        acceptance: {
            supportedBrands: ['VISA', 'MASTERCARD', 'MADA', 'AMERICAN_EXPRESS'],
            supportedCards: 'ALL',
            supportedPaymentAuthentications: ['3DS'],
        },
        features: {
            customerCards: { saveCard: false, autoSaveCard: false },
        },
        interface: {
            locale: config.locale === 'ar' ? 'ar' : 'en',
            theme: 'light',
            edges: 'curved',
            direction: 'ltr',
            powered: false,
            loader: true,
            colorStyle: 'colored',
        },
    }), [config]);

    useImperativeHandle(ref, () => ({
        tokenize: () => {
            if (!isValid) {
                onError?.('Card form is not complete');
                return;
            }

            try {
                pendingRef.current = null;
                tokenize();
            } catch (error) {
                onError?.(error);
            }
        },
    }), [isValid, onError]);

    const emitToken = (payload) => {
        const id = extractTokenId(payload);
        if (id) {
            onToken?.(id);
        } else {
            onError?.('Invalid card token');
        }
    };

    return (
        <div className="menu-tap-card-root">
            <TapCard
                config={cardConfig}
                onError={(err) => onError?.(err)}
                onTokenCreated={(payload, redirectUrl) => {
                    pendingRef.current = payload;
                    if (redirectUrl) {
                        setThreeDsUrl(redirectUrl);
                        return;
                    }
                    emitToken(payload);
                    pendingRef.current = null;
                }}
                onSuccess={(payload) => {
                    if (pendingRef.current) {
                        emitToken({ ...pendingRef.current, ...payload });
                        pendingRef.current = null;
                    } else {
                        emitToken(payload);
                    }
                    setThreeDsUrl('');
                }}
                on3dsRedirect={(data) => {
                    if (data?.url) {
                        setThreeDsUrl(data.url);
                    }
                }}
                on3dsFinish={() => {
                    if (pendingRef.current) {
                        emitToken(pendingRef.current);
                        pendingRef.current = null;
                    }
                    setThreeDsUrl('');
                }}
                on3dsFail={(err) => {
                    pendingRef.current = null;
                    setThreeDsUrl('');
                    onError?.(err);
                }}
                onCompleteTyping={(valid) => setIsValid(valid)}
            />
            {threeDsUrl ? (
                <div className="menu-tap-3ds-overlay">
                    <div className="menu-tap-3ds-modal">
                        <iframe src={threeDsUrl} title="3DS" className="menu-tap-3ds-frame" />
                    </div>
                </div>
            ) : null}
        </div>
    );
});

function CardHost({ api, config, handlers }) {
    const ref = useRef(null);

    useEffect(() => {
        api.tokenize = () => ref.current?.tokenize?.();
    }, [api]);

    return (
        <TapCardMount
            ref={ref}
            config={config}
            onToken={handlers.onToken}
            onError={handlers.onError}
        />
    );
}

const mounts = new Map();

export function mountTapCard(container, config, handlers) {
    if (mounts.has(container)) {
        mounts.get(container).unmount();
    }

    const api = { tokenize: () => {} };
    const root = createRoot(container);
    root.render(<CardHost api={api} config={config} handlers={handlers} />);

    const entry = {
        tokenize: () => api.tokenize(),
        unmount: () => {
            root.unmount();
            mounts.delete(container);
        },
    };

    mounts.set(container, entry);

    return entry;
}

export async function mountApplePay(container, config, handlers) {
    if (mounts.has(container)) {
        mounts.get(container).unmount?.();
    }

    const mod = await import('@tap-payments/apple-pay-button');
    const {
        ApplePayButton,
        Environment,
        Scope,
        SupportedNetworks,
        ThemeMode,
        ButtonType,
        Edges,
        Locale: TapLocale,
    } = mod;

    const root = createRoot(container);
    const locale = config.locale === 'ar' ? TapLocale.AR : TapLocale.EN;

    root.render(
        <ApplePayButton
            publicKey={config.publicKey}
            environment={config.liveMode ? Environment.Production : Environment.Development}
            merchant={{
                domain: config.domain,
                id: config.merchantId,
            }}
            debug={false}
            transaction={{
                amount: String(config.amount),
                currency: config.currency,
            }}
            scope={Scope.TapToken}
            acceptance={{
                supportedBrands: [SupportedNetworks.Visa, SupportedNetworks.MasterCard],
            }}
            customer={{
                name: [{
                    lang: locale,
                    first: config.customer.firstName,
                    last: config.customer.lastName || '',
                    middle: '',
                }],
                contact: {
                    email: config.customer.email || 'guest@moodkw.com',
                    phone: {
                        countryCode: config.customer.phoneCountryCode || '965',
                        number: config.customer.phoneNumber || '',
                    },
                },
            }}
            interface={{
                locale,
                theme: ThemeMode.DARK,
                type: ButtonType.PAY,
                edges: Edges.CURVED,
            }}
            onSuccess={async (payload) => {
                const tokenId = extractTokenId(payload);
                if (!tokenId) {
                    handlers.onError?.('Invalid Apple Pay token');
                    return;
                }
                await handlers.onToken?.(tokenId);
            }}
            onError={(err) => handlers.onError?.(err)}
        />,
    );

    const entry = {
        unmount: () => {
            root.unmount();
            mounts.delete(container);
        },
    };

    mounts.set(container, entry);

    return entry;
}

export function unmountPayment(container) {
    const entry = mounts.get(container);
    if (entry) {
        entry.unmount();
    }
}
