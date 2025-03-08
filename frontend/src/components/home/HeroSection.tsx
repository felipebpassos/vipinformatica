import Link from 'next/link'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faHeadset } from '@fortawesome/free-solid-svg-icons';

export default function HeroSection() {
    return (
        <section
            className="relative text-white px-4 bg-cover bg-center flex items-center justify-center min-h-[max(600px,70vh)]"
            style={{ backgroundImage: "url('/technology_office.png')" }}
        >
            <div className="absolute inset-0 bg-gradient-to-r from-black/80 to-black/70 z-0" />

            <div className="max-w-6xl mx-auto text-center p-8 rounded-lg relative z-10">
                <h1 className="text-4xl md:text-6xl font-bold mb-8 leading-tight">
                    Soluções em Tecnologia para Você e para sua Empresa
                </h1>
                <p className="max-w-4xl text-xl md:text-2xl mb-14 mx-auto text-gray-300 leading-relaxed">
                    Mais de 20 anos no mercado, prestando assessoria na área de TI: manutenção e suporte técnico, criação de sites e sistemas, instalação de redes e servidores, entre outros.
                </p>

                {/* Container principal alinhado ao centro */}
                <div className="flex flex-col m-auto w-fit">

                    {/* Box do ícone + texto alinhado à esquerda */}
                    <div className="mb-2">
                        <div className="flex items-center space-x-2 justify-start">
                            <FontAwesomeIcon
                                icon={faHeadset}
                                className="text-1xl text-gray-400"
                            />
                            <span className="text-sm text-gray-400">Suporte e vendas</span>
                        </div>
                    </div>

                    {/* Botão */}
                    <Link
                        href="/ticket-bot"
                        className="styled-button-red"
                    >
                        Fale com um especialista
                    </Link>
                </div>
            </div>
        </section>
    )
}
