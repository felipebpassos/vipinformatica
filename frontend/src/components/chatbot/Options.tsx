// components/chatbot/Options.tsx
'use client'

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons' // Ícone de WhatsApp

export function Options({
    options,
    onSelect
}: {
    options: string[]
    onSelect: (option: string) => void
}) {
    return (
        <div className="flex flex-col gap-2 items-end">
            {options.map((option) => (
                <button
                    key={option}
                    onClick={() => onSelect(option)}
                    className="bg-gray-700 hover:bg-gray-600 rounded-lg px-4 py-2 w-fit cursor-pointer text-sm sm:text-base flex items-center gap-2 whitespace-nowrap"
                >
                    {option === 'Falar com atendimento' && (
                        <FontAwesomeIcon icon={faWhatsapp} className="text-green-500" />
                    )}
                    {option} {/* Exibe o texto da opção */}
                </button>
            ))}
        </div>
    )
}
