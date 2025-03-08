export default function LoginLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <div className="relative min-h-screen bg-cover bg-center flex items-center justify-center" style={{ backgroundImage: "url('/tech_bg.webp')" }}>
            {/* Overlay */}
            <div className="absolute inset-0 bg-black opacity-60 z-0"></div>

            {/* Conteúdo */}
            <div className="relative z-10">
                {children}
            </div>
        </div>
    )
}
