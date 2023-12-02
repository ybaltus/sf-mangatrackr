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
                'app-clear-purple': '#C41BF7',
                'app-dark-black': '#121212',
                'app-dark-gray1': '#1E2124',
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
            }
        },
    },
    plugins: [
    require('flowbite/plugin') // add the flowbite plugin
    ],
}

