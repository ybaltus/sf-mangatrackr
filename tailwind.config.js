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
                'app-clear-gray2': '#E6E0E9',
                'app-clear-gray3': '#79747E',
                'app-clear-black': '#1D1B20',
                'app-clear-pink1': '#E4D6FE',
                'app-clear-pink2': '#97F8C0',
                'app-clear-blue': '#00306F',
                'app-clear-purple': '#7D31EA',
                'app-clear-purple2': '#D42EF9',
                'app-clear-purple3': '#7024DD',
                'app-clear-purple4': '#FEEBF8',
                'app-clear-purple5': '#B097FB',
                'app-dark-black': '#141218',
                'app-dark-gray': '#1F1F24',
                'app-dark-gray1': '#938F99',
                'app-dark-gray2': '#E6E0E9',
                'app-dark-green1': '#008484',
                'app-dark-green2': '#005454',
                'app-dark-blue1': '#00306F',
                'app-dark-blue2': '#3FFDF0',
                'app-dark-pink': '#97F8C0',
                'app-dark-purple': '#BB9AEE',
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
                'sora-thin': 'Sora-Thin',
                'bangers-regular': 'Bangers-Regular',
            },
            fontSize: {
                'h1-desk': '4rem',
                'h1-tab': '2.7rem',
                'h1-mob': '2.7rem',
                'h2-desk': '2.6rem',
                'h2-tab': '2rem',
                'h2-mob': '2rem',
                'h3-desk': '2rem',
                'h3-tab': '1.4rem',
                'h3-mob': '1.4rem',
                'logo-desk': '1.5rem',
                'logo-tab': '1.5rem',
                'logo-mob': '1.4rem',
                'title-menu-desk': '1.25rem',
                'title-menu-tab': '1.25rem',
                'title-menu-mob': '1.3rem',
                'title-manga-desk': '1.25rem',
                'title-manga-tab': '1.125rem',
                'title-manga-mob': '1.125rem',
                'title-scantheque-desk': '1.25rem',
                'title-scantheque-tab': '1.25rem',
                'title-scantheque-mob': '1.25rem',
                'text-footer-mob': '1rem',
                'text-footer-tab': '1rem',
                'text-footer-desk': '1rem',
                'text-desk': '1.125rem',
                'text-tab': '1rem',
                'text-mob': '1rem',
                'cta-desk': '1.125rem',
                'cta-tab': '1.125rem',
                'cta-mob': '1.125rem',
                'category-manga-desk': '1rem',
                'category-manga-tab': '1rem',
                'category-manga-mob': '1rem',
                'footer-desk': '1.5rem',
                'footer-tab': '1.5rem',
                'footer-mob': '1.4rem',
                'pagination-mob': '2.5rem',
                'pagination-desk': '2.5rem',
                'text-search-desk': '1.2rem',
                'text-search-mob': '0.8rem',
                'text-dropdown-mob': '1rem',
                'text-dropdown-tab': '1rem',
                'text-dropdown-desk': '1.5rem',
                'text-login-mob': '2.5rem',
                'text-login-desk': '1.25rem',
                'h2-about-mob': '2rem',
                'h2-about-tab': '4rem',
                'h2-about-desk': '4rem',
                'h2-about-team-mob': '2rem',
                'h2-about-team-tab': '2.5rem',
                'h2-about-team-desk': '2.5rem',
                'p-characteristics-mob': '1rem',
                'p-characteristics-desk': '1.5rem',
                'cta-nav-mob': '1rem',
                'cta-nav-desk': '1.5rem',
                'label-form-mob': '1.2rem',
                'label-form-desk': '1.5rem',
                'text-form-mob': '1rem',
                'text-form-desk': '1.5rem',
            },
            letterSpacing: {
                'titles-spacing': '0.12rem',
                'titles-jumbotron': '0.07875rem',
                'textes-spacing': '0em',
                'nav-spacing': '0.109rem',
                'footer-spacing': '0.12rem',

            },
            lineHeight: {
                'line-height-auto': "auto",
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
                'manga-card-tab': '90%',
                'manga-card-desk': '80%',
                'manga-scantheque-card-mob': '100%',
                'manga-scantheque-card-tab': '100%',
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
                'img-jumbotron-mob': '16rem',
                'img-jumbotron-tab': '27rem',
                'img-jumbotron-desk': '27rem',
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
                'manga-card-tab': '16rem',
                'manga-card-desk': '20rem',
                'pagination-mob': '2.5rem',
                'pagination-desk': '2.5rem',
                'icon-minus-plus-mob': '1rem',
                'icon-minus-plus-desk': '1.5rem',
                'btn-minus-plus-mob': '2rem',
                'btn-minus-plus-desk': '3rem',
                'manga-scantheque-mob': '13rem',
                'manga-scantheque-tab': '13rem',
                'manga-scantheque-desk': '20rem',
                'manga-detail-mob': '14rem',
                'manga-detail-desk': '20rem',
                'img-jumbotron-mob': '26rem',
                'img-jumbotron-tab': '33rem',
                'img-jumbotron-desk': '33rem',
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
                'manga-scantheque-mob': '3px',
                'manga-scantheque-tab': '3px',
                'manga-scantheque-desk': '3px'
            },
            borderRadius: {
                'manga-scantheque-mob': '1.5rem',
                'manga-scantheque-tab': '1.5rem',
                'manga-scantheque-desk': '1.5rem',
                'info-section': '1.875rem',
                'status-manga': '0.625rem',
            }
        },
    },
    plugins: [
    require('flowbite/plugin') // add the flowbite plugin
    ],
}

