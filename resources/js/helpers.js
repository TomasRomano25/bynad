export const formatMoney = (amount, currency = 'ARS') => {
    if (amount === null || amount === undefined) return '$0';
    const num = parseFloat(amount);
    if (currency === 'USD') {
        return 'US$ ' + num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return '$ ' + num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

export const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

export const months = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

export const accountTypes = {
    banco: 'Banco',
    billetera_virtual: 'Billetera Virtual',
    efectivo: 'Efectivo',
    otro: 'Otro',
};

export const assetTypes = {
    inmueble: 'Inmueble',
    vehiculo: 'Vehiculo',
    inversion: 'Inversion',
    crypto: 'Crypto',
    ahorro: 'Ahorro',
    otro: 'Otro',
};

export const cardBrands = {
    visa: 'Visa',
    mastercard: 'Mastercard',
    amex: 'American Express',
    naranja: 'Naranja',
    cabal: 'Cabal',
    otro: 'Otro',
};

export const fixedExpenseCategories = [
    'Alquiler / Hipoteca',
    'Expensas',
    'Electricidad',
    'Gas',
    'Agua',
    'Internet',
    'Telefonía / Celular',
    'Streaming / Suscripciones',
    'Seguro de vida',
    'Seguro del auto',
    'Seguro de hogar',
    'Salud / Medicina prepaga',
    'Educación / Colegio',
    'Gimnasio / Deporte',
    'Transporte / Nafta',
    'Crédito / Préstamo',
    'Tarjeta de crédito',
    'Otro',
];

export const variableExpenseCategories = [
    'Supermercado',
    'Restaurantes / Salidas',
    'Cafetería',
    'Ropa / Calzado',
    'Electrónica / Tecnología',
    'Salud / Farmacia',
    'Transporte / Taxi / Uber',
    'Nafta / Estacionamiento',
    'Entretenimiento',
    'Viajes / Vacaciones',
    'Mascotas',
    'Belleza / Cuidado personal',
    'Hogar / Decoración',
    'Regalos',
    'Educación',
    'Deporte',
    'Otro',
];
