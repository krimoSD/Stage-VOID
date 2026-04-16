export const config = {
    id: "capital_azur_dynamic_fields:ca_footer",
}

const FooterWidget = ({ data }) => {
    const item = data?.components?.[0]

    const copyrightText = item?.copyright_text || "@2019 CAPITAL AZUR"
    const devText       = item?.dev_text || "Conception et developpement"
    const devUrl        = item?.dev_url || "#"
    const devBadge      = item?.dev_badge_text || "VOID"

    const socialLinks = [
        { label: "LinkedIn", url: item?.social_linkedin_url, icon: "in" },
        { label: "YouTube",  url: item?.social_youtube_url,  icon: "YT" },
        { label: "Twitter",  url: item?.social_twitter_url,  icon: "X"  },
    ].filter((s) => s.url && s.url !== "#")

    const navLinks = [
        { label: item?.nav_1_label, url: item?.nav_1_url },
        { label: item?.nav_2_label, url: item?.nav_2_url },
        { label: item?.nav_3_label, url: item?.nav_3_url },
    ].filter((n) => n.label)

    return (
        <footer className="w-full bg-[#1a1a2e] text-gray-300">
            {/* Main footer */}
            <div className="px-6 lg:px-10 py-10">
                <div className="flex flex-col md:flex-row items-center justify-between gap-6">

                    {/* Left: social icons */}
                    <div className="flex gap-3">
                        {socialLinks.map((s, i) => (
                            <a
                                key={i}
                                href={s.url}
                                aria-label={s.label}
                                className="w-9 h-9 rounded-full border border-gray-600 hover:border-blue-500 hover:bg-blue-600 flex items-center justify-center transition-colors"
                            >
                                <span className="text-xs font-bold text-white">{s.icon}</span>
                            </a>
                        ))}
                    </div>

                    {/* Center: nav links */}
                    <nav className="flex items-center gap-6">
                        {navLinks.map((link, i) => (
                            <a
                                key={i}
                                href={link.url || "#"}
                                className="text-[11px] font-bold uppercase tracking-widest text-gray-400 hover:text-white transition-colors"
                            >
                                {link.label}
                            </a>
                        ))}
                    </nav>

                    {/* Right: logo text */}
                    <div className="text-right leading-none">
                        <div className="text-[13px] font-black tracking-widest text-white uppercase">
                            CAPITAL AZUR
                        </div>
                        <div className="text-[8px] text-gray-500 tracking-wider mt-0.5 uppercase">
                            VOTRE INVESTISSEUR AVANT-GARDE
                        </div>
                    </div>
                </div>
            </div>

            {/* Bottom bar */}
            <div className="border-t border-gray-700/50">
                <div className="px-6 lg:px-10 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p className="text-gray-500 text-xs">{copyrightText}</p>
                    <div className="flex items-center gap-1 text-gray-500 text-xs">
                        <span>{devText}</span>
                        <a href={devUrl} className="font-bold text-blue-400 hover:text-blue-300 ml-1 transition-colors">
                            {devBadge}
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    )
}

export default FooterWidget
