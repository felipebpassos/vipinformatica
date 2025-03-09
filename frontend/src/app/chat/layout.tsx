export default function TicketLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <div className="min-h-screen bg-gray-950 text-white pl-6">
            {children}
        </div>
    )
}