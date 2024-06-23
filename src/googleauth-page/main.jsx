import { createRoot, render, StrictMode, createInterpolateElement } from '@wordpress/element';
import { Button, TextControl, Notice } from '@wordpress/components';
import { useState } from 'react';
import { __ } from '@wordpress/i18n';

import "./scss/style.scss"

const domElement = document.getElementById(window.wpmudevPluginTest.dom_element_id);

const WPMUDEV_PluginTest = () => {
    const [clientId, setClientId] = useState(window.wpmudevPluginTest.clientID || '');
    const [clientSecret, setClientSecret] = useState(window.wpmudevPluginTest.clientSecret || '');
    const [notice, setNotice] = useState(null);

    const handleSave = () => {
        const data = {
            client_id: clientId,
            client_secret: clientSecret,
        };

        fetch('/wp-json/wpmudev/v1/auth/auth-url', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.wpmudevPluginTest.nonce,
            },
            body: JSON.stringify(data),
        })
        .then(response => response.json().then(data => ({status: response.status, body: data})))
        .then(({status, body}) => {
            if (status !== 200) {
                throw new Error(body.message || __('Failed to save settings', 'wpmudev-plugin-test'));
            }
            setNotice({
                status: 'success',
                message: __('Settings saved successfully!', 'wpmudev-plugin-test'),
            });
        })
        .catch(error => {
            setNotice({
                status: 'error',
                message: error.message,
            });
        });
    }

    return (
        <>
            {notice && (
                <Notice status={notice.status} isDismissible={true} onRemove={() => setNotice(null)}>
                    {notice.message}
                </Notice>
            )}
            <div className="sui-header">
                <h1 className="sui-header-title">
                    {__('Settings', 'wpmudev-plugin-test')}
                </h1>
            </div>

            <div className="sui-box">

                <div className="sui-box-header">
                    <h2 className="sui-box-title">{__('Set Google credentials', 'wpmudev-plugin-test')}</h2>
                </div>

                <div className="sui-box-body">
                    <div className="sui-box-settings-row">
                        <TextControl
                            help={createInterpolateElement(
                                __('You can get Client ID from <a>here</a>.', 'wpmudev-plugin-test'),
                                {
                                    a: <a href="https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid"/>,
                                }
                            )}
                            label={__('Client ID', 'wpmudev-plugin-test')}
                            value={clientId}
                            onChange={setClientId}
                        />
                    </div>

                    <div className="sui-box-settings-row">
                        <TextControl
                            type="password"
                            help={createInterpolateElement(
                                __('You can get Client Secret from <a>here</a>.', 'wpmudev-plugin-test'),
                                {
                                    a: <a href="https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid"/>,
                                }
                            )}
                            label={__('Client Secret', 'wpmudev-plugin-test')}
                            value={clientSecret}
                            onChange={setClientSecret}
                        />
                    </div>

                    <div className="sui-box-settings-row">
                        <span>{__('Please use this url', 'wpmudev-plugin-test')} <em>{window.wpmudevPluginTest.returnUrl}</em> {__('in your Google API\'s', 'wpmudev-plugin-test')} <strong>{__('Authorized redirect URIs', 'wpmudev-plugin-test')}</strong> {__('field', 'wpmudev-plugin-test')}</span>
                    </div>
                </div>

                <div className="sui-box-footer">
                    <div className="sui-actions-right">
                        <Button
                            variant="primary"
                            onClick={handleSave}
                        >
                            {__('Save', 'wpmudev-plugin-test')}
                        </Button>
                    </div>
                </div>

            </div>
        </>
    );
}

if (createRoot) {
    createRoot(domElement).render(<StrictMode><WPMUDEV_PluginTest /></StrictMode>);
} else {
    render(<StrictMode><WPMUDEV_PluginTest /></StrictMode>, domElement);
}
