'use client'

import { useState, useEffect } from 'react';
import Link from 'next/link';

export default function CookieBanner() {
    const [isAccepted, setIsAccepted] = useState(false);

    useEffect(() => {
        // Verifica se o cookie de consentimento foi aceito anteriormente
        const cookieConsent = localStorage.getItem('cookieConsent');
        if (cookieConsent === 'accepted') {
            setIsAccepted(true);
        }
    }, []);

    const handleAccept = () => {
        setIsAccepted(true);
        localStorage.setItem('cookieConsent', 'accepted');
    };

    const handleReject = () => {
        setIsAccepted(true);
        localStorage.setItem('cookieConsent', 'rejected');
    };

    if (isAccepted) {
        return null; // Se aceito, não exibe o banner
    }

    return (
        <div className="fixed bottom-5 left-5 right-5 max-w-[400px] bg-light p-8 py-6 rounded-2xl shadow-[0px_8px_46px_rgba(0,0,0,0.2)] z-50">
            <div className="flex-1">
                <h3 className="text-2xl font-bold">Cookies</h3>
                <p className="text-base mt-2">
                    Usamos cookies próprios e de terceiros para personalizar o conteúdo e analisar o tráfego da web.{' '}
                    <Link href="/privacidade" className="font-bold underline">
                        Saiba mais
                    </Link>
                </p>
            </div>
            <div className="flex flex-col sm:flex-row gap-2 mt-4 w-full">
                <button
                    onClick={handleAccept}
                    className="bg-black hover:bg-gray-900 text-white py-3 px-4 cursor-pointer rounded-lg flex-1 font-bold"
                >
                    Aceitar cookies
                </button>
                <button
                    onClick={handleReject}
                    className="bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 px-4 cursor-pointer rounded-lg flex-1 font-bold"
                >
                    Recusar
                </button>
            </div>
        </div>
    );
}