export const validatePayment = (payment: any) => {
  // Verify all information is accurate
  if (!payment.title || typeof payment.title != "string") {
    return "Invalid payment title. Contact us if the error persists.";
  }
  if (!payment.amount || typeof payment.amount != "number") {
    return "Invalid payment amount. Contact us if the error persists.";
  }
  if (typeof payment.isPaid != "boolean") {
    return "Invalid payment property: is Paid. Contact us if the error persists.";
  }
  const regex = /^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/\d{4}$/;
  if (!payment.dueDate || typeof payment.dueDate != "string" || !regex.test(payment.dueDate)) {
    return "Invalid payment date. Contact us if the error persists.";
  }
  if (!["weekly", "bi-weekly", "semi-monthly", "monthly", "one-time"].includes(payment.timePeriod)) {
    return "Invalid payment time period. Contact us if the error persists.";
  }
};

export const formatPrice = (price: Number) => {
  return price.toLocaleString("en-US", { style: "currency", currency: "USD", minimumFractionDigits: 2 });
};

export const getCategoryClasses = (category: string) => {
  // Default styles for all category badges
  const baseClasses = "bg-opacity-15";

  // Map categories to colors
  switch (category.toLowerCase()) {
    case "utilities":
      return `${baseClasses} bg-[#0072DF] text-[#0072DF]`; // accent blue
    case "food":
      return `${baseClasses} bg-[#1D9A38] text-[#1D9A38]`; // success green
    case "transport":
      return `${baseClasses} bg-[#E6AE2C] text-[#E6AE2C]`; // warning yellow
    case "entertainment":
      return `${baseClasses} bg-[#6158FF] text-[#6158FF]`; // secondary purple
    case "health":
      return `${baseClasses} bg-[#E84A8A] text-[#E84A8A]`; // danger pink
    case "fitness":
      return `${baseClasses} bg-[#FF4500] text-[#FF4500]`; // orange red for fitness
    case "personal_care":
      return `${baseClasses} bg-[#DDA0DD] text-[#DDA0DD]`; // plum for personal care
    case "pet":
      return `${baseClasses} bg-[#3CAEA3] text-[#3CAEA3]`; // teal for pets
    case "clothes":
      return `${baseClasses} bg-[#800020] text-[#800020]`; // burgundy
    case "traveling":
      return `${baseClasses} bg-[#FF8C00] text-[#FF8C00]`; // dark orange
    case "education":
      return `${baseClasses} bg-[#9370DB] text-[#9370DB]`; // medium purple
    case "subscriptions":
      return `${baseClasses} bg-[#20B2AA] text-[#20B2AA]`; // light sea green
    case "gifts":
      return `${baseClasses} bg-[#FF1493] text-[#FF1493]`; // deep pink for gifts
    case "taxes":
      return `${baseClasses} bg-[#8B4513] text-[#8B4513]`; // brown
    case "dining":
      return `${baseClasses} bg-[#FF6347] text-[#FF6347]`; // tomato red
    case "housing":
      return `${baseClasses} bg-[#4682B4] text-[#4682B4]`; // steel blue
    case "other":
    default:
      return `${baseClasses} bg-[#808080] text-[#808080]`; // gray for other/default
  }
};
