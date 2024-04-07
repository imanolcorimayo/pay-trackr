export default defineNuxtRouteMiddleware(async (to, from) => {
    // If going to /welcome just continue
    if(to.path.includes('/welcome') || process.server) return;

    const user = await getCurrentUser();

    if (user) {

        return;

    } 

    // If going yo contact-us page, redirect to sign in page
    if(to.path.includes('/contact-us')) {
        return navigateTo({
            path: '/welcome',
            query: {
                redirect: to.fullPath,
            },
        })
    }

    // redirect the user to the login page if not signed in
    return navigateTo({
        path: '/landing',
        /* query: {
            redirect: to.fullPath,
        }, */
    })
})