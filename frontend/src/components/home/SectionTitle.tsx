import React from 'react'

interface SectionTitleProps {
    subtitle: string
    title: string
}

export default function SectionTitle({ subtitle, title }: SectionTitleProps) {
    return (
        <div className="text-center mb-12">
            {/* Subtítulo com fundo transparente e bordas arredondadas */}
            <div className="inline-block bg-gray-100/60 px-4 py-2 rounded-lg">
                <span className="block text-sm font-semibold text-gray-600 uppercase tracking-wider">
                    {subtitle}
                </span>
            </div>
            {/* Título maior */}
            <h1 className="mt-4 text-5xl font-extrabold text-gray-800">
                {title}
            </h1>
        </div>
    )
}