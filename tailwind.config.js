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
                'logo-desk': '1.25rem',
                'logo-mob': '3rem',
                'h1-desk': '4.6rem',
                'h1-mob': '6rem',
                'h2-desk': '2.1rem',
                'h2-mob': '3.5rem',
                'title-manga-desk': '1.25rem',
                'title-manga-mob': '2.5rem',
                'text-desk': '1.25rem',
                'text-mob': '2.5rem',
                'cta-desk': '0.875rem',
                'cta-mob': '1.8rem',
                'footer-desk': '0.938rem',
                'footer-mob': '3rem'
            },
            lineHeight: {
                'h1-desk': '6.5rem',
                'h1-mob': '1rem',
                'h2-desk': '1rem',
                'h2-mob': '1.3rem',
                'title-manga-desk': '2.25rem',
                'title-manga-mob': '1rem',
                'text-desk': '2.25rem',
                'text-mob': '3.5rem',
            }
        },
    },
    plugins: [
    require('flowbite/plugin') // add the flowbite plugin
    ],
}

