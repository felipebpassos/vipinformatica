import Link from 'next/link'

export default function HeroSection() {
    return (
        <section 
            className="relative text-white px-4 bg-cover bg-center flex items-center justify-center min-h-[max(600px,70vh)]" 
            style={{ backgroundImage: "url('/technology_office.png')" }}
        >
            {/* Overlay com gradiente escuro */}
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/70 z-0" />
            
            <div className="max-w-7xl mx-auto text-center p-8 rounded-lg relative z-10">
                <h1 className="text-4xl md:text-6xl font-bold mb-6">
                    Soluções em Tecnologia para seu Negócio
                </h1>
                <p className="text-xl md:text-2xl mb-8">
                    Suporte técnico especializado e desenvolvimento de sistemas sob medida
                </p>
                <Link
                    href="/ticket-bot"
                    className="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-gray-100 transition-colors"
                >
                    Fale com um especialista
                </Link>
            </div>
        </section>
    )
}