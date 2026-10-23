<?php

namespace App;

class DemoCatalog
{
    private const PRODUCTS = [
        'Arroz blanco 1 kg', 'Frijol negro 1 kg', 'Azúcar blanca 1 kg', 'Sal yodada 500 g',
        'Aceite vegetal 1 L', 'Harina de maíz 1 kg', 'Harina de trigo 1 kg', 'Avena en hojuelas 400 g',
        'Café molido 400 g', 'Leche entera 1 L', 'Leche descremada 1 L', 'Chocolate en polvo 400 g',
        'Pasta espagueti 200 g', 'Pasta de coditos 200 g', 'Salsa de tomate 200 g', 'Atún en agua 140 g',
        'Sardinas en tomate 155 g', 'Maíz dulce en lata 400 g', 'Arvejas en lata 400 g', 'Frijoles volteados 400 g',
        'Lentejas 400 g', 'Garbanzos 400 g', 'Miel de abeja 250 g', 'Mermelada de fresa 250 g',
        'Mantequilla de maní 350 g', 'Galletas de avena 200 g', 'Galletas de chocolate 200 g', 'Pan de molde 500 g',
        'Tostadas de maíz 200 g', 'Cereal de maíz 300 g', 'Granola 350 g', 'Pasas 200 g',
        'Maní tostado 200 g', 'Almendras 200 g', 'Agua purificada 1 L', 'Agua mineral 600 ml',
        'Jugo de naranja 1 L', 'Jugo de manzana 1 L', 'Té de manzanilla 20 sobres', 'Té negro 20 sobres',
        'Pimienta negra 50 g', 'Canela molida 50 g', 'Orégano seco 30 g', 'Ajo en polvo 50 g',
        'Vinagre blanco 500 ml', 'Mayonesa 400 g', 'Mostaza 200 g', 'Salsa picante 150 ml',
        'Papel higiénico 4 rollos', 'Toallas de papel 2 rollos', 'Servilletas 100 unidades', 'Pañuelos desechables 100 unidades',
        'Jabón de manos 250 ml', 'Jabón de baño 100 g', 'Champú 400 ml', 'Acondicionador 400 ml',
        'Pasta dental 100 ml', 'Cepillo dental suave', 'Enjuague bucal 500 ml', 'Hilo dental 50 m',
        'Desodorante en barra 50 g', 'Crema corporal 400 ml', 'Protector solar FPS 50 de 100 ml', 'Alcohol antiséptico 70 % de 500 ml',
        'Detergente en polvo 1 kg', 'Detergente líquido 1 L', 'Suavizante de ropa 1 L', 'Cloro 1 L',
        'Desinfectante de pisos 1 L', 'Lavaplatos líquido 750 ml', 'Limpiavidrios 500 ml', 'Esponja de cocina 3 unidades',
        'Bolsas para basura 10 unidades', 'Guantes de limpieza medianos', 'Escoba de cerdas suaves', 'Trapeador de algodón',
        'Cubeta plástica 10 L', 'Paño de microfibra 3 unidades', 'Papel aluminio 10 m', 'Película plástica 30 m',
        'Bolsas herméticas 20 unidades', 'Recipiente para alimentos 1 L', 'Botella reutilizable 750 ml', 'Taza de cerámica 350 ml',
        'Plato de cerámica 25 cm', 'Vaso de vidrio 300 ml', 'Cuchara de acero inoxidable', 'Tenedor de acero inoxidable',
        'Cuaderno de cuadros 100 hojas', 'Cuaderno de líneas 100 hojas', 'Lápiz de grafito HB', 'Bolígrafo azul',
        'Bolígrafo negro', 'Borrador blanco', 'Sacapuntas metálico', 'Regla de 30 cm',
        'Resma de papel carta 500 hojas', 'Pegamento en barra 20 g', 'Cinta adhesiva transparente 20 m', 'Marcador permanente negro',
    ];

    /** @return array{name: string, description: string} */
    public static function product(int $number): array
    {
        $base = self::PRODUCTS[($number - 1) % count(self::PRODUCTS)];
        $units = intdiv($number - 1, count(self::PRODUCTS)) + 1;
        $name = $units === 1 ? $base : $base.' · Paquete de '.$units.' unidades';

        return ['name' => $name, 'description' => $base.'. Presentación de '.($units === 1 ? 'una unidad.' : $units.' unidades.')];
    }

    public static function personName(): string
    {
        return fake('es_ES')->firstName().' '.fake('es_ES')->lastName().' '.fake('es_ES')->lastName();
    }
}
