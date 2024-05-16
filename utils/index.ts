export const validatePayment = (payment: any) => {
    // Verify all information is accurate
    if (!payment.title || typeof payment.title != "string") {
        return 'Invalid payment title.'
    }
    if (!payment.amount || typeof payment.amount != "number") {
        return 'Invalid payment amount.'
    }
    const regex = /^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/\d{4}$/;
    if (!payment.dueDate || typeof payment.dueDate != "string" || !regex.test(payment.dueDate)) {
        return 'Invalid payment date.'
    }
    if(!['weekly', 'bi-weekly', 'semi-monthly', 'monthly', 'one-time'].includes(payment.timePeriod)) {
        return 'Invalid payment time period.';
    }
}

export const formatPrice = (price: Number) => {
    return price.toLocaleString('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 });
}