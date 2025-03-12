import Link from 'next/link'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faHeadset } from '@fortawesome/free-solid-svg-icons';

export default function CTA() {
    return (
        <section className="bg-gray-800 text-white py-32">
            <div className="max-w-6xl mx-auto px-4 flex flex-col items-center text-center lg:flex-row lg:justify-between lg:items-center lg:text-left">

                {/* Título à esquerda (telas grandes) */}
                <h2 className="text-4xl font-bold m-8 lg:mt-0 lg:w-1/2 lg:text-6xl leading-[1.4]">
                    Precisando de suporte?
                </h2>

                {/* Conteúdo da direita (telas grandes) */}
                <div className="lg:w-1/2 lg:pr-8">
                    <p className="mb-8 text-lg">
                        Agende uma consultoria gratuita com<br></br> nossos especialistas.
                    </p>
                    <div className="flex flex-col m-0 w-fit mx-auto lg:mx-0">
                        <div className="mb-2">
                            <div className="flex items-center space-x-2 justify-center lg:justify-start">
                                <FontAwesomeIcon
                                    icon={faHeadset}
                                    className="text-xl text-gray-400"
                                />
                                <span className="text-sm text-gray-400">Suporte e vendas</span>
                            </div>
                        </div>
                        <Link
                            href="/chat"
                            className="styled-button-red"
                        >
                            Fale com um especialista
                        </Link>
                    </div>
                </div>

            </div>
        </section>
    )
}
