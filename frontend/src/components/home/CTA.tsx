import Link from 'next/link'

export default function CTA() {
    return (
        <section className="bg-gray-800 text-white py-16">
            <div className="max-w-7xl mx-auto text-center px-4">
                <h2 className="text-3xl font-bold mb-6">
                    Pronto para Transformar sua Empresa?
                </h2>
                <p className="mb-8 text-lg">
                    Agende uma consultoria gratuita com nossos especialistas
                </p>
                <Link
                    href="/contato"
                    className="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors"
                >
                    Agendar Consultoria
                </Link>
            </div>
        </section>
    )
}