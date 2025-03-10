// app/chat/page.tsx
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

export default function TicketPage() {
    const [step, setStep] = useState<'options' | 'name' | 'email' | 'done'>('options')
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
    const [isTyping, setIsTyping] = useState(false) // Controla o estado de digitação
    const [showInputOrOptions, setShowInputOrOptions] = useState(false) // Controla a exibição de inputs/opções

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

    // Efeito para controlar o indicador de digitação e exibição de inputs/opções
    useEffect(() => {
        const lastGroup = groupedMessages[groupedMessages.length - 1]
        if (lastGroup && !lastGroup[0].isUser) {
            setIsTyping(true)
            const timer = setTimeout(() => {
                setIsTyping(false)
                setShowInputOrOptions(true) // Mostra inputs/opções após a animação
            }, 1500) // Tempo da animação de digitação
            return () => clearTimeout(timer)
        } else {
            setIsTyping(false)
            setShowInputOrOptions(false) // Oculta inputs/opções enquanto há animação
        }
    }, [groupedMessages])

    // Scroll automático
    useEffect(() => {
        window.scrollTo(0, document.body.scrollHeight)
    }, [messageHistory])

    const handleServiceSelect = (service: string) => {
        if (service === 'Falar com atendente') {
            window.location.href = 'https://wa.me/557996761012'
            return
        }

        setMessageHistory((prev) => [
            ...prev,
            { content: service, isUser: true },
            { content: 'Vamos lá, qual seu nome?', isUser: false }
        ])
        setSelectedService(service)
        setStep('name')
        setShowInputOrOptions(false) // Oculta inputs/opções até a animação terminar
    }

    const handleNameSubmit = (name: string) => {
        setName(name)
        setMessageHistory((prev) => [
            ...prev,
            { content: name, isUser: true },
            {
                content: `Perfeito, ${name}!\n\nPara poder identificá-lo e notificar o andamento de serviços, precisaremos do seu email.`,
                isUser: false
            },
            { content: 'Qual o seu melhor e-mail?', isUser: false }
        ])
        setStep('email')
        setShowInputOrOptions(false) // Oculta inputs/opções até a animação terminar
    }

    const handleEmailSubmit = (email: string) => {
        setEmail(email)
        setMessageHistory((prev) => [
            ...prev,
            { content: email, isUser: true },
            {
                content: 'Obrigado! Seu chamado foi aberto. Você receberá uma confirmação por e-mail.',
                isUser: false
            },
            {
                content: 'Seu chamado foi registrado com sucesso!',
                isUser: false
            }
        ])
        setStep('done')
        setShowInputOrOptions(false) // Oculta inputs/opções até a animação terminar
    }

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
                                        alt="Logo da empresa"
                                    />
                                </div>
                            </div>
                        );
                    }
                })}
            </div>

            {/* Exibe inputs/opções apenas após a animação terminar */}
            {showInputOrOptions && (
                <>
                    {step === 'options' && <Options options={services} onSelect={handleServiceSelect} />}

                    {step === 'name' && (
                        <InputWithButton
                            placeholder="Digite seu nome"
                            buttonText="Enviar"
                            onSubmit={handleNameSubmit}
                        />
                    )}

                    {step === 'email' && (
                        <InputWithButton
                            placeholder="Digite seu e-mail"
                            buttonText="Enviar"
                            onSubmit={handleEmailSubmit}
                            type="email"
                        />
                    )}
                </>
            )}
        </div>
    )
}