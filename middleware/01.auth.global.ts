export default defineNuxtRouteMiddleware(async (to, from) => {
    // If going to /welcome just continue
    if(to.path.includes('/welcome') || process.server) return;

    const user = await getCurrentUser();

    // redirect the user to the login page
    if (!user) {
        return navigateTo({
            path: '/welcome',
            query: {
                redirect: to.fullPath,
            },
        })

    } 
})