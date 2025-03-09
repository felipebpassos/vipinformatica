// components/chatbot/Message.tsx
'use client'

export function Message({
    children,
    isUser
}: {
    children: React.ReactNode
    isUser?: boolean
}) {
    return (
        <div className={`flex mb-2 ${isUser ? 'justify-end' : 'justify-start'}`}>
            <div
                className={`max-w-[80%] rounded-lg px-4 py-2 ${isUser ? 'bg-orange-400' : 'bg-gray-800'
                    } whitespace-pre-line`}
            >
                {children}
            </div>
        </div>
    )
}