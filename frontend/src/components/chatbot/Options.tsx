// components/chatbot/Options.tsx
'use client'

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
                    className="bg-gray-800 hover:bg-gray-700 rounded-lg px-4 py-2 w-fit cursor-pointer"
                >
                    {option}
                </button>
            ))}
        </div>
    )
}

