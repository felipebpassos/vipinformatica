import React from 'react'

interface SectionTitleProps {
    subtitle?: string
    title?: string
    maxTitleLength?: number
}

export default function SectionTitle({ subtitle, title, maxTitleLength = 25 }: SectionTitleProps) {
    const formatTitle = (text: string, limit: number) => {
        if (text.length <= limit) return text

        const lastSpaceIndex = text.lastIndexOf(' ', limit) // Encontra o último espaço dentro do limite
        if (lastSpaceIndex === -1) return text // Se não houver espaço, mantém o título original

        return text.slice(0, lastSpaceIndex) + '\n' + text.slice(lastSpaceIndex + 1)
    }

    const formattedTitle = title ? formatTitle(title, maxTitleLength) : ''

    return (
        <div className="text-center mb-12">
            {/* Renderiza o subtítulo apenas se for passado */}
            {subtitle && (
                <div className="inline-block bg-gray-300/40 px-4 py-2 rounded-lg">
                    <span className="block text-sm font-semibold text-gray-600 uppercase tracking-wider">
                        {subtitle}
                    </span>
                </div>
            )}

            {/* Renderiza o título apenas se for passado */}
            {title && (
                <h1 className={`mt-4 text-5xl font-extrabold text-gray-800 ${subtitle ? '' : 'mt-0'} whitespace-pre-line`}>
                    {formattedTitle}
                </h1>
            )}
        </div>
    )
}
