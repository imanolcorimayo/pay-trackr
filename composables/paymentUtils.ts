
// Order function. Used in main "/"" page and on index store page
export const orderPayments = (items: PaymentList) => {
    const { $dayjs } = useNuxtApp();

    // Sort the array
    items.sort((a: Payment, b: Payment) => {
        // Sort by isCompleted (false first)
        if (a.isPaid !== b.isPaid) {
            return a.isPaid ? 1 : -1;
        }

        // If isCompleted is the same, sort by dueDate
        const dueDateA = $dayjs(a.dueDate, { format: 'MM/DD/YYYY' });
        const dueDateB = $dayjs(b.dueDate, { format: 'MM/DD/YYYY' });

        return dueDateA.unix() - dueDateB.unix();
    });

    return items
  }