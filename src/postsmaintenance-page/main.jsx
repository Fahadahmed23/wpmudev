import { createRoot, render, StrictMode } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';
import { useState } from 'react';
import { __ } from '@wordpress/i18n';

import "./scss/style.scss"

const domElement = document.getElementById(window.wpmudevPostMaintenance.dom_element_id);

const WPMUDEV_PostMaintenance = () => {
    const [notice, setNotice] = useState(null);

    const handleScan = () => {
        fetch('/wp-json/wpmudev/v1/posts/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.wpmudevPostMaintenance.nonce,
            },
        })
        .then(response => response.json().then(data => ({status: response.status, body: data})))
        .then(({status, body}) => {
            if (status !== 200) {
                throw new Error(body.message || __('Failed to scan posts', 'wpmudev-plugin-test'));
            }
            setNotice({
                status: 'success',
                message: __('Posts scanned successfully!', 'wpmudev-plugin-test'),
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
                    {__('Posts Maintenance', 'wpmudev-plugin-test')}
                </h1>
            </div>

            <div className="sui-box">
                <div className="sui-box-header">
                    <h2 className="sui-box-title">{__('Scan Posts', 'wpmudev-plugin-test')}</h2>
                </div>

                <div className="sui-box-body">
                    <p>{__('Click the button below to scan all public posts and pages.', 'wpmudev-plugin-test')}</p>
                </div>

                <div className="sui-box-footer">
                    <div className="sui-actions-right">
                        <Button
                            variant="primary"
                            onClick={handleScan}
                        >
                            {__('Scan Posts', 'wpmudev-plugin-test')}
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

if (createRoot) {
    createRoot(domElement).render(<StrictMode><WPMUDEV_PostMaintenance /></StrictMode>);
} else {
    render(<StrictMode><WPMUDEV_PostMaintenance /></StrictMode>, domElement);
}
