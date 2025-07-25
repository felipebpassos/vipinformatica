'use client'

import { useState, useEffect, useMemo, Fragment } from 'react'
import { Message, TypingIndicator } from '@/components/chatbot/Message'
import { Options } from '@/components/chatbot/Options'
import { InputWithButton } from '@/components/chatbot/InputWithButton'

const services = [
    'Manutenção e conserto de equipamentos',
    'Formatação e instalação de programas',
    'Desenvolvimento de sites e sistemas',
    'Consultoria em TI',
    'Falar com atendimento'
]

// Service explanations - removed the prompt at the end
const serviceExplanations = {
    'Manutenção e conserto de equipamentos':
        'Ótimo!\n\n' +
        'Nosso serviço de manutenção e conserto de equipamentos funciona da seguinte forma:\n\n' +
        '1. Primeiramente, realizamos uma avaliação técnica do seu equipamento para identificar todos os problemas.\n' +
        '2. Em seguida, apresentamos um orçamento detalhado para sua aprovação.\n' +
        '3. Após aprovação, iniciamos os reparos necessários utilizando peças de qualidade.\n' +
        '4. Finalizamos com testes completos para garantir o funcionamento perfeito.',

    'Formatação e instalação de programas':
        'Ótimo!\n\n' +
        'Nosso serviço de formatação e instalação de programas segue estas etapas:\n\n' +
        '1. Realizamos um backup dos seus arquivos importantes (quando solicitado).\n' +
        '2. Formatamos o dispositivo e instalamos o sistema operacional de sua preferência.\n' +
        '3. Configuramos drivers e programas essenciais (antivírus, pacote office, navegadores, etc).\n' +
        '4. Finalizamos com uma verificação completa para garantir o funcionamento adequado.',

    'Desenvolvimento de sites e sistemas':
        'Ótimo!\n\n' +
        'Nosso processo de desenvolvimento de sites e sistemas inclui as seguintes fases:\n\n' +
        '1. Realizamos uma reunião de alinhamento para levantamento de requisitos, entendendo suas necessidades e objetivos.\n' +
        '2. Apresentamos uma proposta detalhada com prazos e valores para sua aprovação.\n' +
        '3. Após o fechamento, iniciamos o desenvolvimento com atualizações regulares sobre o progresso.\n' +
        '4. Entregamos o projeto finalizado com suporte para ajustes e melhorias.',

    'Consultoria em TI':
        'Ótimo!\n\n' +
        'Nossa consultoria em TI funciona da seguinte maneira:\n\n' +
        '1. Iniciamos com uma reunião diagnóstica para entender os desafios e necessidades da sua empresa.\n' +
        '2. Elaboramos um plano estratégico personalizado com soluções e recomendações.\n' +
        '3. Apresentamos o plano de ação com orçamento e cronograma para implementação.\n' +
        '4. Acompanhamos a implementação das soluções e fornecemos suporte contínuo.'
}

// Common message for all services
const continuePrompt = 'Para prosseguir, precisaremos de algumas informações como nome, email e whatsapp para que um de nossos especialistas possa entrar em contato. Deseja continuar?'

export default function TicketPage() {
    const [step, setStep] = useState<'options' | 'serviceConfirm' | 'name' | 'email' | 'whatsapp' | 'done'>('options')
    const [messageHistory, setMessageHistory] = useState([
        {
            content: 'Olá! 😊 Seja muito bem-vindo(a) ao atendimento da VIP.com Informática!\n\nSe precisar de suporte técnico, orçamentos ou qualquer outra coisa, é só me avisar! Estou aqui para ajudar.',
            isUser: false
        },
        { content: 'O que você precisa?', isUser: false }
    ])
    const [selectedService, setSelectedService] = useState('')
    const [name, setName] = useState('')
    const [email, setEmail] = useState('')
    const [whatsapp, setWhatsapp] = useState('')
    const [isTyping, setIsTyping] = useState(false)
    const [showInputOrOptions, setShowInputOrOptions] = useState(false)

    // New state for validation errors
    const [emailError, setEmailError] = useState('')
    const [whatsappError, setWhatsappError] = useState('')

    // Email validation function
    const validateEmail = (email: string): boolean => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        return emailRegex.test(email)
    }

    // Brazilian phone number validation function
    const validateWhatsapp = (phone: string): boolean => {
        // Regex para validar números brasileiros sem exigir o +55
        const phoneRegex = /^(\(?\d{2}\)?)\s?(\d{4,5}[-]?\d{4})$/
        return phoneRegex.test(phone.replace(/\s+/g, ''))
    }

    const groupedMessages = useMemo(() => {
        const groups: typeof messageHistory[] = []
        for (const msg of messageHistory) {
            if (msg.isUser) {
                groups.push([msg])
            } else {
                const lastGroup = groups[groups.length - 1]
                if (lastGroup && !lastGroup[0].isUser) {
                    lastGroup.push(msg)
                } else {
                    groups.push([msg])
                }
            }
        }
        return groups
    }, [messageHistory])

    useEffect(() => {
        const lastGroup = groupedMessages[groupedMessages.length - 1]
        if (lastGroup && !lastGroup[0].isUser) {
            setIsTyping(true)
            const timer = setTimeout(() => {
                setIsTyping(false)
                setShowInputOrOptions(true)
            }, 1500)
            return () => clearTimeout(timer)
        } else {
            setIsTyping(false)
            setShowInputOrOptions(false)
        }
    }, [groupedMessages])

    useEffect(() => {
        window.scrollTo(0, document.body.scrollHeight)
    }, [messageHistory])

    const handleServiceSelect = (service: string) => {
        if (service === 'Falar com atendimento') {
            window.location.href = 'https://wa.me/557996761012'
            return
        }

        setMessageHistory((prev) => [
            ...prev,
            { content: service, isUser: true },
            { content: serviceExplanations[service as keyof typeof serviceExplanations], isUser: false },
            { content: continuePrompt, isUser: false } // Add as a separate message
        ])
        setSelectedService(service)
        setStep('serviceConfirm')
        setShowInputOrOptions(false)
    }

    const handleServiceConfirm = () => {
        setMessageHistory((prev) => [
            ...prev,
            { content: 'Sim, desejo continuar.', isUser: true },
            { content: 'Vamos lá, qual seu nome?', isUser: false }
        ])
        setStep('name')
        setShowInputOrOptions(false)
    }

    const handleNameSubmit = (name: string) => {
        setName(name)
        setMessageHistory((prev) => [
            ...prev,
            { content: name, isUser: true },
            {
                content: `Perfeito, ${name.split(' ')[0]}!`,
                isUser: false
            },
            { content: 'Qual o seu melhor e-mail?', isUser: false }
        ])
        setStep('email')
        setShowInputOrOptions(false)
    }

    const handleEmailSubmit = (email: string) => {
        if (!validateEmail(email)) {
            setEmailError('Por favor, insira um e-mail válido.')
            return
        }

        // Clear any previous email error
        setEmailError('')
        setEmail(email)
        setMessageHistory((prev) => [
            ...prev,
            { content: email, isUser: true },
            {
                content: `Quase lá, ${name.split(' ')[0]}!`,
                isUser: false
            },
            { content: 'Por favor, informe abaixo seu número de whatsapp', isUser: false }
        ])
        setStep('whatsapp')
        setShowInputOrOptions(false)
    }

    const handleWhatsappSubmit = (whatsapp: string) => {
        if (!validateWhatsapp(whatsapp)) {
            setWhatsappError('Por favor, insira um número de WhatsApp válido com DDD (ex: 79 9 9999-9999).')
            return
        }

        // Clear any previous whatsapp error
        setWhatsappError('')
        setWhatsapp(whatsapp)
        setMessageHistory((prev) => [
            ...prev,
            { content: whatsapp, isUser: true },
            {
                content: `Tudo certo, ${name.split(' ')[0]}! 👍`,
                isUser: false
            },
            {
                content: 'Seu chamado foi aberto com sucesso.',
                isUser: false
            },
            {
                content: 'Você receberá uma confirmação por e-mail e nossa equipe entrará em contato em breve para dar andamento ao seu atendimento.',
                isUser: false
            }
        ])
        setStep('done')
        setShowInputOrOptions(false)
    }

    const handleSubmitDone = () => {
        // Envio para API
        const formData = {
            name: name,
            email: email,
            phone: whatsapp,
            service: selectedService
        }

        fetch('https://api.felipepassos.dev', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData),
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição')
                }
                return response.json()
            })
            .then(data => {
                console.log('Sucesso:', data)
            })
            .catch((error) => {
                console.error('Erro:', error)
            })
    }

    useEffect(() => {
        if (step === 'done') {
            handleSubmitDone()
        }
    }, [step])

    return (
        <div className="max-w-3xl mx-auto px-6 pb-12 pt-40">
            <div className="flex-1 space-y-4">
                {groupedMessages.map((group, groupIndex) => {
                    if (group[0].isUser) {
                        return group.map((msg, msgIndex) => (
                            <Message key={`${groupIndex}-${msgIndex}`} isUser={true}>
                                {msg.content.split('\n\n').map((paragraph, i, paragraphs) => (
                                    <p key={i} className={i < paragraphs.length - 1 ? 'mb-2' : ''}>
                                        {paragraph.split('\n').map((line, j, lines) => (
                                            <Fragment key={j}>
                                                {line}
                                                {j < lines.length - 1 && <br />}
                                            </Fragment>
                                        ))}
                                    </p>
                                ))}
                            </Message>
                        ))
                    } else {
                        const isLastGroup = groupIndex === groupedMessages.length - 1
                        return (
                            <div key={groupIndex} className="relative pl-0 mb-0">
                                {isLastGroup && isTyping ? (
                                    <TypingIndicator />
                                ) : (
                                    group.map((msg, msgIndex) => (
                                        <Message key={`${groupIndex}-${msgIndex}`} isUser={false}>
                                            {msg.content.split('\n\n').map((paragraph, i, paragraphs) => (
                                                <p key={i} className={i < paragraphs.length - 1 ? 'mb-4' : ''}>
                                                    {paragraph.split('\n').map((line, j, lines) => (
                                                        <Fragment key={j}>
                                                            {line}
                                                            {j < lines.length - 1 && <br />}
                                                        </Fragment>
                                                    ))}
                                                </p>
                                            ))}
                                        </Message>
                                    ))
                                )}
                                <div className="absolute left-0 bottom-0 w-8 h-8 rounded-full -translate-x-10 mb-1 flex justify-center items-center">
                                    <img
                                        src="chat-man.webp"
                                        className="w-full h-full object-cover rounded-full"
                                        alt="Atendente"
                                    />
                                </div>
                            </div>
                        );
                    }
                })}
            </div>

            {showInputOrOptions && (
                <>
                    {step === 'options' && <Options options={services} onSelect={handleServiceSelect} />}

                    {step === 'serviceConfirm' && (
                        <Options
                            options={['Sim, desejo continuar.']}
                            onSelect={() => handleServiceConfirm()}
                        />
                    )}

                    {step === 'name' && (
                        <InputWithButton
                            placeholder="Digite seu nome"
                            buttonText="Enviar"
                            onSubmit={handleNameSubmit}
                        />
                    )}

                    {step === 'email' && (
                        <div>
                            <InputWithButton
                                placeholder="Digite seu e-mail"
                                buttonText="Enviar"
                                onSubmit={handleEmailSubmit}
                                type="email"
                            />
                            {emailError && (
                                <div className="flex justify-end w-full">
                                    <span className="text-red-500 mt-2 text-sm w-[400px]">
                                        {emailError}
                                    </span>
                                </div>
                            )}
                        </div>
                    )}

                    {step === 'whatsapp' && (
                        <div>
                            <InputWithButton
                                placeholder="Digite seu WhatsApp com DDD"
                                buttonText="Enviar"
                                onSubmit={handleWhatsappSubmit}
                                type="tel"
                            />
                            {whatsappError && (
                                <div className="flex justify-end w-full">
                                    <span className="text-red-500 mt-2 text-sm w-[400px]">
                                        {whatsappError}
                                    </span>
                                </div>
                            )}
                        </div>
                    )}
                </>
            )}
        </div>
    )
}