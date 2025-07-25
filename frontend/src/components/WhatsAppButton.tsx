// components/WhatsAppButton.tsx
"use client";
import { useState, useEffect } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTimes } from '@fortawesome/free-solid-svg-icons';
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons';

export default function WhatsAppButton() {
    const [showButton, setShowButton] = useState(false);
    const [showMessage, setShowMessage] = useState(false);

    useEffect(() => {
        const buttonTimer = setTimeout(() => setShowButton(true), 3000);
        const messageTimer = setTimeout(() => setShowMessage(true), 4000);

        return () => {
            clearTimeout(buttonTimer);
            clearTimeout(messageTimer);
        };
    }, []);

    return (
        <>
            {showMessage && (
                <div className={`message-box ${showMessage ? 'show' : ''}`}>
                    <span className="texto">
                        Fale conosco
                        <span className="close" onClick={() => setShowMessage(false)}>
                            <FontAwesomeIcon icon={faTimes} />
                        </span>
                    </span>
                </div>
            )}

            <div className={`whatsapp-box ${showButton ? 'show' : ''}`}>
                <a
                    href="https://api.whatsapp.com/send/?phone=557930272614&text&type=phone_number&app_absent=0"
                    className="whatsapp-button"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span className="notification-badge">1</span>
                    <FontAwesomeIcon icon={faWhatsapp} className="whatsapp-icon" />
                    <span className="button-text">Contato</span>
                </a>
            </div>
        </>
    );
}