export default function LoginLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-100 to-white flex items-center justify-center">
            {children}
        </div>
    )
}