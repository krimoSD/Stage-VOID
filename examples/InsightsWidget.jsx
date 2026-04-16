export const config = {
    id: "capital_azur_dynamic_fields:ca_insights",
}

const resolveImageSrc = (src) => {
    if (!src) return null
    const url = typeof src === "string" ? src : (src._default || src[Object.keys(src)[0]] || null)
    if (!url) return null
    if (url.startsWith("http://") || url.startsWith("https://")) {
        try { return new URL(url).pathname } catch { return url }
    }
    return url
}

const categoryColors = {
    "Actualité": "#0d1b3e",
    "Point de vue": "#0d1b3e",
    "Analyse": "#0d1b3e",
}

const InsightCard = ({ image, category, date, title, url }) => {

    return (
        <article className="flex flex-col">
            {/* Image */}
            <a href={url || "#"} className="block overflow-hidden rounded-lg mb-4">
                {image ? (
                    <img
                        src={image}
                        alt={title || ""}
                        className="w-full h-52 object-cover transition-transform duration-300 hover:scale-105"
                    />
                ) : (
                    <div className="w-full h-52 bg-gray-200 rounded-lg" />
                )}
            </a>

            {/* Meta */}
            <div className="flex items-center gap-3 mb-3">
                {category && (
                    <span
                        className="inline-block px-3 py-1 rounded-full text-white text-xs font-semibold uppercase tracking-wide"
                        style={{ backgroundColor: "#0d1b3e" }}
                    >
                        {category}
                    </span>
                )}
                {date && (
                    <span className="text-gray-500 text-sm">{date}</span>
                )}
            </div>

            {/* Title */}
            {title && (
                <h3 className="text-gray-900 font-bold text-base leading-snug mb-4 flex-1">
                    {title}
                </h3>
            )}

            {/* CTA */}
            <a
                href={url || "#"}
                className="text-sm font-bold uppercase tracking-wider"
                style={{ color: "#1a6bc4" }}
            >
                Lire plus
            </a>
        </article>
    )
}

const InsightsWidget = ({ data }) => {
    console.log(data)
    const sectionTitle = data?.extra_field?.group_header?.section_title
    const sectionSubtitle = data?.extra_field?.group_header?.section_subtitle
    const buttonLabel = data?.extra_field?.group_footer?.button_label
    const buttonUrl = data?.extra_field?.group_footer?.button_url

    const cards = (data?.components || []).map((item) => ({
        image: resolveImageSrc(item?.image?.[0]?._default),
        category: item?.category_name,
        date: item?.date,
        title: item?.title,
        url: item?.link_url,
    }))

    if (cards.length === 0) return null

    return (
        <section className="w-full bg-white py-16">
            <div className="container mx-auto px-6 lg:px-10">
                {/* Header */}
                <div className="mb-10 text-center">
                    {sectionTitle && (
                        <div className="flex items-center justify-center gap-4 mb-4">
                            <div className="h-1 w-8 rounded-full" style={{ backgroundColor: "#1a6bc4" }} />
                            <h2 className="text-2xl md:text-3xl font-extrabold text-gray-900 uppercase tracking-widest">
                                {sectionTitle}
                            </h2>
                        </div>
                    )}
                    {sectionSubtitle && (
                        <p className="text-gray-500 text-sm max-w-xl mx-auto leading-relaxed">
                            {sectionSubtitle}
                        </p>
                    )}
                </div>

                {/* Cards grid */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    {cards.map((card, i) => (
                        <InsightCard key={i} {...card} />
                    ))}
                </div>

                {/* Footer button */}
                {buttonLabel && (
                    <div className="flex justify-center">
                        <a
                            href={buttonUrl || "#"}
                            className="inline-block px-8 py-3 border-2 text-sm font-bold uppercase tracking-widest transition-colors duration-200 hover:text-white"
                            style={{
                                borderColor: "#0d1b3e",
                                color: "#0d1b3e",
                            }}
                            onMouseEnter={(e) => {
                                e.currentTarget.style.backgroundColor = "#0d1b3e"
                                e.currentTarget.style.color = "#ffffff"
                            }}
                            onMouseLeave={(e) => {
                                e.currentTarget.style.backgroundColor = "transparent"
                                e.currentTarget.style.color = "#0d1b3e"
                            }}
                        >
                            {buttonLabel}
                        </a>
                    </div>
                )}
            </div>
        </section>
    )
}

export default InsightsWidget
