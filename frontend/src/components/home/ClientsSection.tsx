"use client"
import { useState, useRef, useEffect } from 'react'
import { clients } from '@/lib/constants'
import SectionTitle from '../SectionTitle'

export default function ClientsSection() {
    const [speedTop, setSpeedTop] = useState(1)       // Velocidade inicial da linha de cima
    const [speedBottom, setSpeedBottom] = useState(1) // Velocidade inicial da linha de baixo (positiva)
    const animationRefs = useRef<Animation[]>([])
    const isDragging = useRef(false)
    const startX = useRef(0)
    const currentSpeed = useRef({ top: 1, bottom: 1 })
    const activeLine = useRef<'top' | 'bottom' | null>(null)
    const resizeObservers = useRef<ResizeObserver[]>([])
    const topContentRef = useRef<HTMLDivElement>(null)
    const bottomContentRef = useRef<HTMLDivElement>(null)

    useEffect(() => {
        const elements = [
            topContentRef.current,
            bottomContentRef.current
        ].filter(Boolean) as HTMLDivElement[]

        elements.forEach((el, index) => {
            const updateAnimation = () => {
                const contentWidth = el.scrollWidth
                const duration = (contentWidth / window.innerWidth) * 20000

                const keyframesTop = [
                    { transform: 'translateX(0)' },
                    { transform: `translateX(-${contentWidth / 2}px)` }
                ]
                const keyframesBottom = [
                    { transform: `translateX(-${contentWidth / 2}px)` },
                    { transform: 'translateX(0)' }
                ]

                if (animationRefs.current[index]) {
                    animationRefs.current[index].cancel()
                }

                const animation = el.animate(
                    index === 0 ? keyframesTop : keyframesBottom,
                    { duration, iterations: Infinity }
                )

                animation.playbackRate = index === 0 ? speedTop : speedBottom
                animationRefs.current[index] = animation
            }

            const resizeObserver = new ResizeObserver(updateAnimation)
            resizeObserver.observe(el)
            resizeObservers.current.push(resizeObserver)
            updateAnimation()
        })

        return () => {
            animationRefs.current.forEach(anim => anim?.cancel())
            resizeObservers.current.forEach(observer => observer.disconnect())
        }
    }, [])

    useEffect(() => {
        if (animationRefs.current[0]?.playbackRate !== undefined) {
            animationRefs.current[0].playbackRate = speedTop
        }
        if (animationRefs.current[1]?.playbackRate !== undefined) {
            animationRefs.current[1].playbackRate = speedBottom
        }
    }, [speedTop, speedBottom])

    const handleDragStart = (e: React.MouseEvent | React.TouchEvent) => {
        isDragging.current = true
        const target = e.target as HTMLElement
        const container = target.closest('.marquee-container')

        if (container) {
            activeLine.current = container.classList.contains('top-line')
                ? 'top'
                : 'bottom'
            startX.current = 'touches' in e ? e.touches[0].clientX : e.clientX
            currentSpeed.current = { top: speedTop, bottom: speedBottom }
        }

        document.addEventListener('mousemove', handleDrag)
        document.addEventListener('mouseup', handleDragEnd)
        document.addEventListener('touchmove', handleDrag)
        document.addEventListener('touchend', handleDragEnd)
    }

    const handleDrag = (e: MouseEvent | TouchEvent) => {
        if (!isDragging.current || !activeLine.current) return
        e.preventDefault()

        const currentX =
            'touches' in e ? e.touches[0].clientX : (e as MouseEvent).clientX
        const delta = (startX.current - currentX) * 0.015

        if (activeLine.current === 'top') {
            const newSpeed = currentSpeed.current.top + delta
            setSpeedTop(Math.min(Math.max(newSpeed, -3), 3))
        } else {
            // inverte o delta para a linha de baixo
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
                <SectionTitle title="Parceiros que confiam em nosso trabalho" />

                <div
                    className="space-y-8 cursor-grab active:cursor-grabbing w-full"
                    onMouseDown={handleDragStart}
                    onTouchStart={handleDragStart}
                >
                    {/* Linha superior */}
                    <div className="marquee-container top-line overflow-hidden w-full">
                        <div
                            ref={topContentRef}
                            className="marquee-content flex w-max gap-20"
                        >
                            {[...clients, ...clients].map((client, i) => (
                                <div
                                    key={`top-${i}`}
                                    className="relative h-30 w-50 flex-shrink-0 transition-all rounded-3xl overflow-hidden"
                                >
                                    <img
                                        src={client.logo}
                                        alt={client.alt}
                                        className="object-contain"
                                        sizes="(max-width: 768px) 100px, 200px"
                                    />
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Linha inferior */}
                    <div className="marquee-container bottom-line overflow-hidden w-full">
                        <div
                            ref={bottomContentRef}
                            className="marquee-content flex w-max gap-20"
                        >
                            {[...clients, ...clients].map((client, i) => (
                                <div
                                    key={`bottom-${i}`}
                                    className="relative h-30 w-50 flex-shrink-0 transition-all rounded-3xl overflow-hidden"
                                >
                                    <img
                                        src={client.logo}
                                        alt={client.alt}
                                        className="object-contain"
                                        sizes="(max-width: 768px) 100px, 200px"
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
