import { jsPDF } from "jspdf";

function formatReceiptDate(dateValue) {
  if (!dateValue) return 'N/A';

  const parsedDate = new Date(dateValue);
  if (Number.isNaN(parsedDate.getTime())) {
    return String(dateValue).split('T')[0] || 'N/A';
  }

  return parsedDate.toLocaleDateString('en-IN', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
}

function formatReceiptCurrency(value) {
  const num = Number(value || 0);
  const rounded = Math.round((num + Number.EPSILON) * 100) / 100;
  const hasFraction = Math.abs(rounded % 1) > 0;
  const opts = { maximumFractionDigits: 2, minimumFractionDigits: hasFraction ? 2 : 0 };
  let s = rounded.toLocaleString('en-US', opts);
  return 'INR ' + s;
}

export function generateReceiptPdf(details) {
  const {
    receiptTitle,
    receiptType,
    seat_number,
    member_name,
    phone,
    registration_number,
    payment_date,
    months_paid,
    total_amount,
    amount_cash,
    amount_upi,
    due_date
  } = details;

  const doc = new jsPDF({ unit: 'pt', format: 'a4' });
  const margin = 40;
  const pageWidth = doc.internal.pageSize.getWidth();
  const rightColumnX = pageWidth - margin;
  let y = 40;

  const cashAmount = parseFloat(amount_cash) || 0;
  const upiAmount = parseFloat(amount_upi) || 0;
  const totalAmount = parseFloat(total_amount) || cashAmount + upiAmount;
  const paymentTypeLabel = receiptType?.includes('Renewal')
    ? 'Membership Renewal Payment'
    : receiptType?.includes('Booking')
      ? 'Seat Booking Payment'
      : 'Membership Payment';

  // Colored header band
  doc.setFillColor(10, 102, 194);
  doc.rect(margin - 8, 24, pageWidth - (margin * 2) + 16, 90, 'F');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(20);
  doc.setTextColor(255, 255, 255);
  doc.text(`Clever's Reading Room`, margin, 54);

  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');
  doc.setTextColor(220, 230, 250);
  doc.text('2, 3 Musa Point, Musa Road, Vyara', margin, 74);
  doc.text('Phone: +91 8780256801 | +91 9723342930', margin, 92);

  doc.setFont('helvetica', 'bold');
  doc.setTextColor(20, 35, 56);
  doc.text(receiptTitle || 'Receipt', rightColumnX, 54, { align: 'right' });
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.text(paymentTypeLabel, rightColumnX, 74, { align: 'right' });
  doc.text(`Date: ${formatReceiptDate(payment_date)}`, rightColumnX, 92, { align: 'right' });

  y = 132;
  doc.setDrawColor(204, 204, 204);
  doc.setLineWidth(0.8);
  doc.line(margin, y, pageWidth - margin, y);
  y += 24;

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(12);
  doc.setTextColor(20, 35, 56);
  doc.text('Customer Details', margin, y);
  y += 18;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.setTextColor(60, 70, 90);
  doc.text(`Name: ${member_name || 'N/A'}`, margin, y);
  y += 14;
  doc.text(`Phone: ${phone || 'N/A'}`, margin, y);
  y += 14;
  if (registration_number) {
    doc.text(`Registration ID: ${registration_number}`, margin, y);
    y += 14;
  }
  doc.text(`Seat No.: ${seat_number || 'N/A'}`, margin, y);
  y += 14;
  doc.text(`Membership Valid Until: ${formatReceiptDate(due_date)}`, margin, y);

  y += 24;
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(12);
  doc.setTextColor(20, 35, 56);
  doc.text('Payment Summary', margin, y);
  y += 18;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.text(`Transaction Type: ${paymentTypeLabel}`, margin, y);
  y += 14;
  doc.text(`Duration: ${months_paid || 0} Month${months_paid === 1 ? '' : 's'}`, margin, y);
  y += 14;
  doc.text(`Payment Date: ${formatReceiptDate(payment_date)}`, margin, y);

  y += 24;
  doc.setDrawColor(221, 221, 221);
  doc.setLineWidth(0.6);
  doc.line(margin, y, pageWidth - margin, y);
  y += 18;

  doc.setFont('helvetica', 'bold');
  doc.setTextColor(20, 35, 56);
  doc.text('Payment Breakdown', margin, y);
  doc.text('Amount (INR)', rightColumnX, y, { align: 'right' });
  y += 16;
  doc.setDrawColor(238, 240, 242);
  doc.line(margin, y, pageWidth - margin, y);
  y += 18;

  doc.setFont('helvetica', 'normal');
  doc.setTextColor(60, 70, 90);
  doc.text('Cash', margin, y);
  doc.text(formatReceiptCurrency(cashAmount), rightColumnX, y, { align: 'right' });
  y += 16;
  doc.text('UPI', margin, y);
  doc.text(formatReceiptCurrency(upiAmount), rightColumnX, y, { align: 'right' });
  y += 18;

  doc.setFont('helvetica', 'bold');
  doc.setTextColor(20, 35, 56);
  doc.text('Total Paid', margin, y);
  doc.text(formatReceiptCurrency(totalAmount), rightColumnX, y, { align: 'right' });

  y += 28;
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9);
  doc.setTextColor(95, 103, 117);
  doc.text('This is a computer-generated receipt and does not require a signature.', margin, y);
  y += 16;
  doc.text('Please keep this receipt for your reference.', margin, y);

  y += 28;
  doc.setDrawColor(204, 204, 204);
  doc.line(margin, y, margin + 180, y);
  doc.text('Authorized Signature', margin, y + 16);

  const safeDate = formatReceiptDate(payment_date).replace(/[^a-zA-Z0-9]/g, '-');
  const fileNameSeat = seat_number
    ? `receipt-seat-${seat_number}-${safeDate}.pdf`
    : `receipt-${safeDate}.pdf`;
  doc.save(fileNameSeat);
}
