// app/chat/page.tsx
'use client'

import { useState, useEffect, useMemo  } from 'react'
import { Message } from '@/components/chatbot/Message'
import { Options } from '@/components/chatbot/Options'
import { InputWithButton } from '@/components/chatbot/InputWithButton'

const services = [
    'Manutenção e suporte técnico',
    'Criação de sites',
    'Desenvolvimento de software',
    'Consultoria em TI',
    'Falar com atendente'
]

export default function TicketPage() {
    const [step, setStep] = useState<'options' | 'name' | 'email' | 'done'>('options')
    const [messageHistory, setMessageHistory] = useState([
        {
            content: 'Olá! 😊 Seja muito bem-vindo(a) ao atendimento da VIP.com Informática!\n\nSe precisar de suporte técnico, orçamentos ou qualquer outra coisa, é só me avisar! Estou aqui para ajudar.',
            isUser: false
        },
        { content: 'O que você procura?', isUser: false }
    ])
    const [selectedService, setSelectedService] = useState('')
    const [name, setName] = useState('')
    const [email, setEmail] = useState('')

    const handleServiceSelect = (service: string) => {
        if (service === 'Falar com atendente') {
            window.location.href = 'https://wa.me/SEUNUMERO'
            return
        }

        setMessageHistory((prev) => [
            ...prev,
            { content: service, isUser: true },
            { content: 'Vamos lá, qual seu nome?', isUser: false }
        ])
        setSelectedService(service)
        setStep('name')
    }

    const handleNameSubmit = (name: string) => {
        setName(name)
        setMessageHistory((prev) => [
            ...prev,
            { content: name, isUser: true },
            {
                content: `Fechou, ${name}\n\nQual o seu melhor e-mail?\n\n[O email é para acessar o sistema e ver o andamento do serviço]`,
                isUser: false
            }
        ])
        setStep('email')
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
    }

    // Adicione um useEffect para scroll automático
    useEffect(() => {
        window.scrollTo(0, document.body.scrollHeight)
    }, [messageHistory])

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

    return (
        <div className="max-w-3xl mx-auto px-6 py-12">
            <div className="flex-1 space-y-4">
                {groupedMessages.map((group, groupIndex) => {
                    if (group[0].isUser) {
                        return group.map((msg, msgIndex) => (
                            <Message key={`${groupIndex}-${msgIndex}`} isUser={true}>
                                {msg.content.split('\n').map((line, i) => (
                                    <p key={i} className="mb-0">{line}</p>
                                ))}
                            </Message>
                        ))
                    } else {
                        return (
                            <div key={groupIndex} className="relative pl-0 mb-0">
                                {group.map((msg, msgIndex) => (
                                    <Message key={`${groupIndex}-${msgIndex}`} isUser={false}>
                                        {msg.content.split('\n').map((line, i) => (
                                            <p key={i} className="mb-0">{line}</p>
                                        ))}
                                    </Message>
                                ))}
                                <img
                                    src="logo-mini.png"
                                    className="absolute left-0 bottom-0 w-8 h-8 rounded-full -translate-x-10 mb-1"
                                    alt="Logo da empresa"
                                />
                            </div>
                        )
                    }
                })}
            </div>

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

        </div>
    )
}