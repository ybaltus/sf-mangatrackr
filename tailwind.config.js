/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
    "./node_modules/flowbite/**/*.js" // set up the path to the flowbite package
    ],
    theme: {
        extend: {
            colors: {
                'app-clear-white': '#FFFFFF',
                'app-clear-gray': '#F6F6F6',
                'app-clear-black': '#1E2124',
                'app-clear-pink1': '#E4D6FE',
                'app-clear-pink2': '#FCD3E9',
                'app-clear-blue': '#00306F',
                'app-clear-purple': '#7D31EA',
                'app-clear-purple2': '#D42EF9',
                'app-dark-black': '#121212',
                'app-dark-gray1': '#1F1F24',
                'app-dark-gray2': '#E8E9E9',
                'app-dark-green1': '#008484',
                'app-dark-green2': '#005454',
                'app-dark-blue1': '#00306F',
                'app-dark-blue2': '#3FFDF0',
                'app-focus-purple': '#7D31EA',
                'hr-clear-mob': '#9ca3af',
                'hr-clear-desk': '#d1d5db',
                'hr-dark': '#4b5563'
            },
            fontFamily: {
                'sora-bold': 'Sora-Bold',
                'sora-extraBold': 'Sora-ExtraBold',
                'sora-extraLight': 'Sora-ExtraLight',
                'sora-light': 'Sora-Light',
                'sora-medium': 'Sora-Medium',
                'sora-regular': 'Sora-Regular',
                'sora-semiBold': 'Sora-SemiBold',
                'sora-thin': 'Sora-Thin'
            },
            fontSize: {
                'logo-desk': '1.5rem',
                'logo-mob': '1.4rem',
                'h1-desk': '4rem',
                'h1-mob': '2.25rem',
                'h2-desk': '2rem',
                'h2-mob': '1.7rem',
                'title-manga-desk': '1.3rem',
                'title-manga-mob': '1.25rem',
                'title-scantheque-desk': '1.5rem',
                'title-scantheque-mob': '1rem',
                'text-desk': '1.25rem',
                'text-mob': '1rem',
                'cta-desk': '1.3rem',
                'cta-mob': '1.1rem',
                'footer-desk': '1.25rem',
                'footer-mob': '0.9rem',
                'title-menu-desk': '1rem',
                'title-menu-mob': '1.2rem',
                'pagination-mob': '2.5rem',
                'pagination-desk': '2.5rem',
                'text-search-desk': '1.2rem',
                'text-search-mob': '0.8rem',
                'text-dropdown-mob': '1rem',
                'text-dropdown-desk': '1.5rem',
                'text-login-mob': '2.5rem',
                'text-login-desk': '1.25rem'
            },
            lineHeight: {
                'h1-desk': '1.5',
                'h1-mob': '1.5',
                'h2-desk': '1.5',
                'h2-mob': '1.5',
                'title-manga-desk': '1.5',
                'title-manga-mob': '1.5',
                'text-desk': '1.5',
                'text-mob': '1.5',
            },
            width: {
                'icon-nav-mode-mob': '1.875rem',
                'icon-nav-mode-desk': '2rem',
                'icon-nav-user-mob': '1.875rem',
                'icon-nav-user-desk': '1.5rem',
                'icon-nav-menu-mob': '1.875rem',
                'icon-hp-arrow-mob': '1.875rem',
                'icon-hp-arrow-desk': '4rem',
                'manga-card-mob': '100%',
                'manga-card-desk': '80%',
                'manga-scantheque-card-mob': '100%',
                'manga-scantheque-card-desk': '100%',
                'pagination-mob': '2.5rem',
                'pagination-desk': '2.5rem',
                'searchbar-mob': '50%',
                'searchbar-desk': '50%',
                'icon-minus-plus-mob': '1rem',
                'icon-minus-plus-desk': '1.5rem',
                'btn-minus-plus-mob': '1.5em',
                'btn-minus-plus-desk': '2rem',
                'manga-scantheque-mob': '100%',
                'manga-scantheque-desk': '100%',
                'manga-detail-mob': '60%',
                'manga-detail-desk': '70%',
            },
            height: {
                'icon-nav-mode-mob': '1.875rem',
                'icon-nav-mode-desk': '2rem',
                'icon-nav-user-mob': '1.5rem',
                'icon-nav-user-desk': '1.5rem',
                'icon-nav-menu-mob': '1.875rem',
                'icon-hp-arrow-mob': '1.875rem',
                'icon-hp-arrow-desk': '4rem',
                'manga-card-mob': '14rem',
                'manga-card-desk': '20rem',
                'pagination-mob': '2.5rem',
                'pagination-desk': '2.5rem',
                'icon-minus-plus-mob': '1rem',
                'icon-minus-plus-desk': '1.5rem',
                'btn-minus-plus-mob': '2rem',
                'btn-minus-plus-desk': '3rem',
                'manga-scantheque-mob': '13rem',
                'manga-scantheque-desk': '20rem',
                'manga-detail-mob': '30rem',
                'manga-detail-desk': '20rem',
            },
            space: {
                'icon-navbar-mob': '0.5rem',
                'icon-navbar-desk': '1rem',
                'menu-navbar-x': '2rem',
                'menu-navbar-y-mob': '2rem',
                'menu-navbar-y-desk': '0px',
            },
            margin: {
                'menu-mob-top': '3rem',
                'menu-desk-top': '0px',
                'arrow-hp-mob-top': '5rem',
                'arrow-hp-desk-top': '5rem',
                'section-h2-mob-bottom': '4rem',
                'section-h2-desk-bottom': '6rem'
            },
            padding: {
                'menu-mob': '',
                'menu-desk': '0px',
                'manga-card': '0.3rem'
            },
            textUnderlineOffset: {
                'menu-mob': '7px',
                'menu-desk': '10px'
            },
            textDecorationThickness: {
                'menu-mob': '5px',
                'menu-desk': '4px'
            },
            borderWidth: {
                'manga-card-mob': '2px',
                'manga-card-desk': '3px',
                'manga-scantheque-mob': '2px',
                'manga-scantheque-desk': '3px'
            },
            borderRadius: {
                'manga-scantheque-mob': '1.5rem',
                'manga-scantheque-desk': '1.5rem'
            }
        },
    },
    plugins: [
    require('flowbite/plugin') // add the flowbite plugin
    ],
}

