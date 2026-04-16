import { useRef } from "react"

export const config = {
    id: "capital_azur_dynamic_fields:ca_services",
}

const resolveImageSrc = (src) => {
    if (!src) return null
    const url = typeof src === "string" ? src : (src._original || src[Object.keys(src)[0]] || null)
    if (!url) return null
    if (url.startsWith("http://") || url.startsWith("https://")) {
        try { return new URL(url).pathname } catch { return url }
    }
    return url
}

const ServiceCard = ({ icon, title, url }) => {
    const Wrapper = url ? "a" : "div"
    const wrapperProps = url ? { href: url } : {}

    return (
        <Wrapper
            {...wrapperProps}
            className="shrink-0 w-44 flex flex-col items-center bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow cursor-pointer"
        >
            <div className="w-20 h-20 mb-5 flex items-center justify-center">
                {icon ? (
                    <img src={icon} alt={title || ""} className="w-full h-full object-contain" />
                ) : (
                    <svg className="w-14 h-14" viewBox="0 0 80 80" fill="none">
                        <rect width="80" height="80" rx="16" fill="#EEF4FF" />
                        <path d="M20 50 Q30 30 40 40 Q50 50 60 30" stroke="#4A90D9" strokeWidth="2.5" strokeLinecap="round" fill="none" />
                        <circle cx="55" cy="28" r="4" fill="#FF6B6B" opacity="0.7" />
                        <path d="M22 55 h36" stroke="#B0C4DE" strokeWidth="2" strokeLinecap="round" />
                    </svg>
                )}
            </div>
            {title && (
                <span className="text-sm font-semibold text-gray-800 text-center leading-snug">
                    {title}
                </span>
            )}
        </Wrapper>
    )
}

const ServicesWidget = ({ data }) => {
    const trackRef = useRef(null)
    const SCROLL_BY = 200

    const sectionTitle = data?.extra_field?.group_header?.section_title
    const sectionContent = data?.extra_field?.group_header?.section_description

    const services = data?.components?.map((item) => ({
        icon: resolveImageSrc(item?.icon?.[0]?._default),
        title: item?.label,
        url: item?.link_url || null,
    })) || []

    if (services.length === 0) return null

    const scrollLeft = () => trackRef.current?.scrollBy({ left: -SCROLL_BY, behavior: "smooth" })
    const scrollRight = () => trackRef.current?.scrollBy({ left: SCROLL_BY, behavior: "smooth" })

    return (
        <section className="w-full py-12" style={{ backgroundColor: "#EBF3FD" }}>
            <div className="container mx-auto px-6 lg:px-10">
                {/* Header */}
                <div className="mb-10 max-w-2xl mx-auto flex flex-col items-center text-center">
                    {sectionTitle && (
                        <div className="flex flex-col items-center mb-4">
                            <h2 className="text-2xl md:text-3xl font-extrabold text-gray-900 uppercase leading-tight tracking-wide">
                                {sectionTitle}
                            </h2>
                            <div className="h-1 w-16 bg-blue-600 rounded-full mt-4" />
                        </div>
                    )}
                    {sectionContent && (
                        <p className="text-gray-600 text-sm leading-relaxed">
                            {sectionContent}
                        </p>
                    )}
                </div>

                {/* Carousel */}
                <div className="relative flex items-center justify-center">
                    <button
                        onClick={scrollLeft}
                        className="shrink-0 mr-4 w-10 h-10 rounded-full bg-blue-600 hover:bg-blue-700 flex items-center justify-center text-white shadow transition-colors z-10"
                        aria-label="Previous"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <div
                        ref={trackRef}
                        className="flex gap-5 overflow-x-auto scroll-smooth pb-2 justify-center w-full min-w-0"
                        style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
                    >
                        {services.map((service, i) => (
                            <ServiceCard key={i} {...service} />
                        ))}
                    </div>

                    <button
                        onClick={scrollRight}
                        className="shrink-0 ml-4 w-10 h-10 rounded-full bg-blue-600 hover:bg-blue-700 flex items-center justify-center text-white shadow transition-colors z-10"
                        aria-label="Next"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </section>
    )
}

export default ServicesWidget
