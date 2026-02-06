class GeminiHandler {
  constructor(apiKey) {
    this.apiKey = apiKey;
    this.model = 'gemini-2.5-flash-lite';
    this.baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
  }

  async generateContent(prompt, { maxOutputTokens = 500, temperature = 0.7 } = {}) {
    try {
      const response = await fetch(
        `${this.baseUrl}/${this.model}:generateContent?key=${this.apiKey}`,
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            contents: [{ parts: [{ text: prompt }] }],
            generationConfig: { maxOutputTokens, temperature }
          })
        }
      );

      if (!response.ok) {
        console.error('Gemini API error:', await response.text());
        return null;
      }

      const result = await response.json();
      return result.candidates?.[0]?.content?.parts?.[0]?.text || null;
    } catch (error) {
      console.error('Error calling Gemini:', error);
      return null;
    }
  }

  async getFinancialAnalysis(dataSummary) {
    const prompt = `Eres un asesor financiero personal amigable. Analiza los siguientes datos de gastos de un usuario argentino y proporciona feedback conciso sobre su salud financiera.

DATOS DEL USUARIO:
- Meses analizados: ${dataSummary.months.join(', ')}
- Total de pagos registrados: ${dataSummary.totalPayments}
- Gastos fijos mensuales: $${dataSummary.totalRecurrent} (${dataSummary.recurrentCount} recurrentes)

GASTOS POR MES:
${Object.entries(dataSummary.monthlyData).map(([month, info]) =>
  `${month}: $${info.total.toLocaleString('es-AR')} (${info.count} pagos)
   Categorias: ${Object.entries(info.byCategory).map(([cat, amt]) => `${cat}: $${amt.toLocaleString('es-AR')}`).join(', ')}`
).join('\n\n')}

RECURRENTES PRINCIPALES:
${dataSummary.recurrents.map(r => `- ${r.title}: $${r.amount.toLocaleString('es-AR')} (${r.category})`).join('\n')}

INSTRUCCIONES:
1. Analiza tendencias de gasto (subiendo, bajando, estable)
2. Identifica categorias con mayor gasto. Identifica posible gastos irresponsables, evitables o anomalos
3. Evalua la proporcion de gastos fijos vs variables
4. Da 2-3 consejos practicos y especificos
5. Usa un tono amigable y motivador. No marques errores ni juzgues los habitos. Sos el aliado que quiere ayudar.
6. NO uses emojis
7. Responde en espanol argentino
8. Manten la respuesta CORTA (max 800 caracteres) para WhatsApp
9. Usa *asteriscos* para negritas
10. Si aplica, haz notar algun patron interesante en los datos

Responde directamente con el analisis, sin introduccion.`;

    const text = await this.generateContent(prompt, { maxOutputTokens: 500, temperature: 0.7 });
    return text || 'No se pudo completar el analisis. Intenta nuevamente.';
  }

  async getWeeklyInsight(weeklyStats) {
    const pastWeek = weeklyStats.pastWeek;
    const nextWeek = weeklyStats.nextWeek;

    const prompt = `Sos un asesor financiero personal amigable. Analizá los datos semanales de un usuario argentino y armá un resumen breve con dos partes:

1. RESUMEN: En 2-3 oraciones contá qué pasó la semana pasada y qué se viene. Mencioná qué fue lo más pesado, qué estuvo tranquilo, si viene bien o atrasado con los pagos.
2. TIPS: 1-2 comentarios genéricos, amigables y motivadores. NO sugieras acciones concretas (no "pagá tal cosa", "revisá tal otra"). Solo observaciones positivas o datos curiosos sobre sus finanzas.

DATOS:
- Semana pasada: ${pastWeek.count} pagos por $${pastWeek.amount.toLocaleString('es-AR')} (${pastWeek.paidCount} pagados, ${pastWeek.unpaidCount} pendientes por $${pastWeek.unpaidAmount.toLocaleString('es-AR')})
- Semana entrante: ${nextWeek.count} pagos por $${nextWeek.amount.toLocaleString('es-AR')} (${nextWeek.paidCount} pagados, ${nextWeek.unpaidCount} pendientes por $${nextWeek.unpaidAmount.toLocaleString('es-AR')})
- Total pendiente ambas semanas: $${weeklyStats.totalUnpaidAmount.toLocaleString('es-AR')}
- Pagados este mes: ${weeklyStats.paidThisMonth}
- Pendientes este mes: ${weeklyStats.unpaidThisMonth}
- Total pagado este mes: $${weeklyStats.totalPaidAmount.toLocaleString('es-AR')}
- Gastos únicos este mes: ${weeklyStats.oneTimeCount} por $${weeklyStats.oneTimeAmount.toLocaleString('es-AR')}

REGLAS:
- Español argentino, tono amigable y cercano
- Sin emojis
- No uses encabezados ni listas, escribí todo como texto corrido
- Separá el resumen de los tips con un salto de línea
- Máximo 600 caracteres en total
- Respondé directamente, sin introducción`;

    return await this.generateContent(prompt, { maxOutputTokens: 350, temperature: 0.8 });
  }
}

export default GeminiHandler;
