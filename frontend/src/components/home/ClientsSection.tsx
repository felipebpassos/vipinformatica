import Image from 'next/image'
import { clients } from '@/lib/constants'

export default function ClientsSection() {
    return (
        <section className="py-16">
            <div className="max-w-7xl mx-auto px-4">
                <h2 className="text-3xl font-bold text-center mb-12 text-gray-800">
                    Clientes que Confiam em Nós
                </h2>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-8 items-center">
                    {clients.map((client) => (
                        <div
                            key={client.alt}
                            className="relative h-20 grayscale hover:grayscale-0 transition-all"
                        >
                            <Image
                                src={client.logo}
                                alt={client.alt}
                                fill
                                className="object-contain p-2"
                            />
                        </div>
                    ))}
                </div>
            </div>
        </section>
    )
}