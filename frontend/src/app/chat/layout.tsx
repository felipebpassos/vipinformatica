import Link from 'next/link'

export default function TicketLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <div className="relative min-h-screen bg-gray-950 text-white pl-6">
            <Link href="/" className="absolute top-10 left-1/2 -translate-x-1/2">
                <img
                    src="/logo-2.png"
                    alt="Logo"
                    className="h-16 w-auto hover:opacity-80 transition-opacity"
                />
            </Link>
            {children}
        </div>
    )
}
