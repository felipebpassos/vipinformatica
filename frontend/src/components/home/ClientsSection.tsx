"use client"
import { useState, useRef, useEffect } from 'react'
import Image from 'next/image'
import { clients } from '@/lib/constants'
import SectionTitle from '../SectionTitle'

export default function ClientsSection() {
    const [speedTop, setSpeedTop] = useState(1)
    const [speedBottom, setSpeedBottom] = useState(1) // Alterado para positivo
    const animationRefs = useRef<Animation[]>([])
    const isDragging = useRef(false)
    const startX = useRef(0)
    const currentSpeed = useRef({ top: 1, bottom: 1 })
    const activeLine = useRef<'top' | 'bottom' | null>(null)
    const resizeObservers = useRef<ResizeObserver[]>([])

    useEffect(() => {
        const elements = document.querySelectorAll('.marquee-content')

        elements.forEach((el, index) => {
            const updateAnimation = () => {
                const contentWidth = el.scrollWidth
                const viewportWidth = window.innerWidth
                const duration = (contentWidth / viewportWidth) * 40000 // Reduzido o multiplicador

                // Define os keyframes com base na linha
                const keyframes = index === 0
                    ? [
                        { transform: 'translateX(0px)' },
                        { transform: `translateX(-${contentWidth / 2}px)` }
                    ]
                    : [
                        { transform: `translateX(-${contentWidth / 2}px)` },
                        { transform: 'translateX(0px)' }
                    ]

                // Cancela animação existente
                if (animationRefs.current[index]) {
                    animationRefs.current[index].cancel()
                }

                // Cria nova animação
                const animation = el.animate(keyframes, {
                    duration: duration,
                    iterations: Infinity
                })

                animation.playbackRate = index === 0 ? speedTop : speedBottom
                animationRefs.current[index] = animation
            }

            // Observador de redimensionamento
            const resizeObserver = new ResizeObserver(updateAnimation)
            resizeObserver.observe(el)
            resizeObservers.current.push(resizeObserver)

            // Atualização inicial
            updateAnimation()
        })

        return () => {
            animationRefs.current.forEach(anim => anim?.cancel())
            resizeObservers.current.forEach(observer => observer.disconnect())
        }
    }, [])

    useEffect(() => {
        animationRefs.current[0]?.playbackRate !== undefined && (animationRefs.current[0].playbackRate = speedTop)
        animationRefs.current[1]?.playbackRate !== undefined && (animationRefs.current[1].playbackRate = speedBottom)
    }, [speedTop, speedBottom])

    const handleDragStart = (e: React.MouseEvent | React.TouchEvent) => {
        isDragging.current = true
        const target = e.target as HTMLElement
        const container = target.closest('.marquee-container')

        if (container) {
            activeLine.current = container.classList.contains('top-line') ? 'top' : 'bottom'
            startX.current = 'touches' in e ? e.touches[0].clientX : e.clientX
            currentSpeed.current = {
                top: speedTop,
                bottom: speedBottom
            }
        }

        document.addEventListener('mousemove', handleDrag)
        document.addEventListener('mouseup', handleDragEnd)
        document.addEventListener('touchmove', handleDrag)
        document.addEventListener('touchend', handleDragEnd)
    }

    const handleDrag = (e: MouseEvent | TouchEvent) => {
        if (!isDragging.current || !activeLine.current) return
        e.preventDefault()

        const currentX = 'touches' in e ? e.touches[0].clientX : (e as MouseEvent).clientX
        const delta = (currentX - startX.current) * 0.015

        if (activeLine.current === 'top') {
            const newSpeed = currentSpeed.current.top - delta
            setSpeedTop(Math.min(Math.max(newSpeed, -3), 3))
        } else {
            const newSpeed = currentSpeed.current.bottom - delta
            setSpeedBottom(Math.min(Math.max(newSpeed, -3), 3))
        }
    }

    const handleDragEnd = () => {
        isDragging.current = false
        activeLine.current = null
        document.removeEventListener('mousemove', handleDrag)
        document.removeEventListener('mouseup', handleDragEnd)
        document.removeEventListener('touchmove', handleDrag)
        document.removeEventListener('touchend', handleDragEnd)
    }

    return (
        <section className="py-32 overflow-hidden w-full">
            <div className="px-4 w-full">
                <SectionTitle
                    title="Parceiros que confiam em nosso trabalho"
                />

                <div
                    className="space-y-8 cursor-grab active:cursor-grabbing w-full"
                    onMouseDown={handleDragStart}
                    onTouchStart={handleDragStart}
                >
                    {/* Linha superior - Aumenta o número de cópias */}
                    <div className="marquee-container top-line overflow-hidden w-full">
                        <div className="marquee-content flex w-max gap-20">
                            {[...clients, ...clients, ...clients, ...clients].map((client, i) => (
                                <div
                                    key={`top-${i}`}
                                    className="relative h-30 w-50 flex-shrink-0 transition-all rounded-3xl overflow-hidden"
                                >
                                    <Image
                                        src={client.logo}
                                        alt={client.alt}
                                        fill
                                        className="object-contain"
                                    />
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Linha inferior - Aumenta o número de cópias */}
                    <div className="marquee-container bottom-line overflow-hidden w-full">
                        <div className="marquee-content flex w-max gap-20">
                            {[...clients, ...clients, ...clients, ...clients].map((client, i) => (
                                <div
                                    key={`bottom-${i}`}
                                    className="relative h-30 w-50 flex-shrink-0 transition-all rounded-3xl overflow-hidden"
                                >
                                    <Image
                                        src={client.logo}
                                        alt={client.alt}
                                        fill
                                        className="object-contain"
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    )
}