// components/chatbot/InputWithButton.tsx
'use client'

import { useState } from 'react'

export function InputWithButton({
    placeholder,
    buttonText,
    onSubmit,
    type = 'text'
}: {
    placeholder: string
    buttonText: string
    onSubmit: (value: string) => void
    type?: string
}) {
    const [value, setValue] = useState('')

    const handleSubmit = () => {
        if (value.trim()) {
            onSubmit(value.trim())
            setValue('')
        }
    }

    return (
        <div className="w-full flex justify-end">
            <div className="flex gap-2 bg-gray-800 p-2 rounded-lg w-[400px] max-w-full">
                <input
                    type={type}
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    placeholder={placeholder}
                    className="flex-1 text-white p-2 focus:outline-none"
                    onKeyPress={(e) => e.key === 'Enter' && handleSubmit()}
                />
                <button
                    onClick={handleSubmit}
                    className="bg-blue-600 hover:bg-blue-700 rounded-lg py-2 px-4 cursor-pointer text-sm sm:text-base"
                >
                    {buttonText}
                </button>
            </div>
        </div>
    )
}

