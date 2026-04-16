import { useMenu } from "@vactorynext/core/hooks"
import { useState } from "react"

export const config = {
    id: "capital_azur_dynamic_fields:ca_header",
}

const HeaderWidget = ({ data }) => {
    const [menuOpen, setMenuOpen] = useState(false)
    const menu = useMenu('main')
    console.log(menu)
    const item = data?.components?.[0]

    const logoText    = item?.logo_text || "CAPITAL AZUR"
    const logoSub     = item?.logo_subtitle || "VOTRE INVESTISSEUR AVANT-GARDE"
    const ctaLabel    = item?.cta_label || "BANQUE DIGITALE"
    const ctaUrl      = item?.cta_url || "#"

    const navItems = [
        { label: item?.menu_1_label, url: item?.menu_1_url || "#" },
        { label: item?.menu_2_label, url: item?.menu_2_url || "#" },
        { label: item?.menu_3_label, url: item?.menu_3_url || "#" },
    ].filter((n) => n.label)

    return (
        <header className="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div className="max-w-7xl mx-auto px-6 lg:px-10">
                <div className="flex items-center justify-between h-[70px]">

                    {/* Logo */}
                    <a href="/" className="flex items-center gap-2 shrink-0">
                        {/* Blue square placeholder logo */}
                        <div className="w-8 h-8 bg-blue-600 rounded-sm flex items-center justify-center">
                            <span className="text-white text-xs font-bold">CA</span>
                        </div>
                        <div className="leading-none">
                            <div className="text-[13px] font-black tracking-widest text-gray-900 uppercase">
                                {logoText}
                            </div>
                            <div className="text-[8px] text-gray-400 tracking-wider mt-0.5 uppercase">
                                {logoSub}
                            </div>
                        </div>
                    </a>

                    {/* Desktop Nav */}
                    <nav className="hidden md:flex items-center">
                        {navItems.map((item, i) => (
                            <div key={i} className="flex items-center">
                                {i > 0 && (
                                    <span className="w-px h-4 bg-gray-300 mx-1" />
                                )}
                                <a
                                    href={item.url}
                                    className="px-4 py-1.5 text-[11px] font-bold uppercase tracking-widest text-gray-700 hover:text-blue-600 transition-colors"
                                >
                                    {item.label}
                                </a>
                            </div>
                        ))}
                    </nav>

                    {/* CTA */}
                    <div className="hidden md:flex items-center">
                        <a
                            href={ctaUrl}
                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-md bg-[#0d1b3e] text-white text-[11px] font-bold uppercase tracking-widest hover:bg-[#162850] transition-colors"
                        >
                            {ctaLabel}
                            <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 1a5 5 0 0 0-5 5v3H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V11a2 2 0 0 0-2-2h-2V6a5 5 0 0 0-5-5zm0 2a3 3 0 0 1 3 3v3H9V6a3 3 0 0 1 3-3zm0 9a2 2 0 1 1 0 4 2 2 0 0 1 0-4z" />
                            </svg>
                        </a>
                    </div>

                    {/* Mobile burger */}
                    <button
                        onClick={() => setMenuOpen((v) => !v)}
                        className="md:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-md"
                        aria-label="Menu"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {menuOpen
                                ? <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                : <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            }
                        </svg>
                    </button>
                </div>
            </div>

            {/* Mobile menu */}
            {menuOpen && (
                <div className="md:hidden border-t border-gray-100 bg-white">
                    <nav className="px-6 py-4 flex flex-col gap-1">
                        {navItems.map((item, i) => (
                            <a
                                key={i}
                                href={item.url}
                                className="py-2 text-sm font-semibold uppercase tracking-wide text-gray-700"
                            >
                                {item.label}
                            </a>
                        ))}
                        <a
                            href={ctaUrl}
                            className="mt-3 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-md bg-[#0d1b3e] text-white text-sm font-bold uppercase tracking-wide"
                        >
                            {ctaLabel}
                        </a>
                    </nav>
                </div>
            )}
        </header>
    )
}

export default HeaderWidget
