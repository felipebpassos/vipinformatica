// components/chatbot/Message.tsx
'use client'

import { useState, useEffect } from 'react'

export function TypingIndicator() {
    return (
        <div className="flex space-x-1 p-2">
            <div className="w-2 h-2 bg-gray-400 rounded-full animate-wave" />
            <div className="w-2 h-2 bg-gray-400 rounded-full animate-wave delay-150" />
            <div className="w-2 h-2 bg-gray-400 rounded-full animate-wave delay-300" />
        </div>
    )
}

export function Message({
    children,
    isUser
}: {
    children: React.ReactNode
    isUser?: boolean
}) {
    const [isVisible, setIsVisible] = useState(isUser)

    useEffect(() => {
        if (!isUser) setIsVisible(true)
    }, [isUser])

    return (
        <div className={`flex mb-2 ${isUser ? 'justify-end' : 'justify-start'}`}>
            <div
                className={`max-w-[80%] rounded-lg px-4 py-2 ${
                    isUser ? 'bg-red-600' : 'bg-gray-800'
                } whitespace-pre-line transition-opacity duration-500 ${
                    isVisible ? 'opacity-100' : 'opacity-0'
                }`}
                style={{
                    transformOrigin: isUser ? 'bottom right' : 'bottom left'
                }}
            >
                {children}
            </div>
        </div>
    )
}