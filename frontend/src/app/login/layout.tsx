import Link from 'next/link'

export default function LoginLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <div className="relative min-h-screen bg-cover bg-center flex items-center justify-center" style={{ backgroundImage: "url('/tech_bg.webp')" }}>
            <div className="absolute inset-0 bg-black opacity-70 z-0"></div>

            <div className="relative z-10 flex flex-col items-center gap-8">
                {/* Logo adicionado aqui para ficar acima do formulário */}
                <Link href="/" className="inline-block">
                    <img 
                        src="/logo-2.png" 
                        alt="Logo" 
                        className="h-16 w-auto hover:opacity-80 transition-opacity"
                    />
                </Link>
                {children}
            </div>
        </div>
    )
}