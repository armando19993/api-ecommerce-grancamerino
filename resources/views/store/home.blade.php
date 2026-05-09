<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>El Gran Camerino 33 | Camisetas de fútbol 1.1</title>
    <meta name="description" content="Camisetas de fútbol 1.1, clubes, selecciones, retro y edición jugador. Envíos a toda Colombia.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('grancamerino/assets/premium.css') }}">
</head>
<body>
    <div class="topbar">
        <div><span>🚚</span> Envíos a toda Colombia</div>
        <div><span>💳</span> Pago contra entrega</div>
        <div><span>🔁</span> Cambios gratuitos</div>
        <a href="https://wa.me/573168930015" target="_blank" rel="noopener">WhatsApp: 316 693 0015</a>
    </div>

    <header class="site-header">
        <a href="/" class="brand">
            <img src="{{ asset('grancamerino/assets/logo.png') }}" alt="El Gran Camerino 33">
        </a>
        <form class="search" action="/productos" method="get">
            <input name="q" type="search" placeholder="Buscar camisetas, equipos o selecciones...">
            <button type="submit">Buscar</button>
        </form>
        <nav class="header-actions">
            <a href="/login">Mi cuenta</a>
            <a href="/favoritos">Favoritos</a>
            <a class="cart" href="/carrito">Carrito</a>
        </nav>
    </header>

    <nav class="main-nav">
        <a class="active" href="/">Inicio</a>
        <a href="/productos?categoria=clubes">Clubes</a>
        <a href="/productos?categoria=selecciones">Selecciones</a>
        <a href="/productos?categoria=nuevas">Nuevas camisetas</a>
        <a href="/productos?categoria=edicion-jugador">Edición jugador</a>
        <a href="/productos?categoria=retro">Retro</a>
        <a href="/productos?categoria=ofertas">Ofertas</a>
    </nav>

    @php
        $heroProducts = $products->take(3);
        $fallbackImages = [
            'https://res.cloudinary.com/dww5s0b7p/image/upload/v1773950136/categorys/qo7fu3wzuka3xdf8p9ih.jpg',
            'https://res.cloudinary.com/dww5s0b7p/image/upload/v1773950157/categorys/mxch4wcnoza0fta071w7.jpg',
            'https://res.cloudinary.com/dww5s0b7p/image/upload/v1773950182/categorys/zzxwwxwc86mxttwxouyf.jpg',
        ];
    @endphp

    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow">Calidad premium 1.1</span>
            <h1>Las mejores camisetas de fútbol para tu colección.</h1>
            <p>Clubes, selecciones, retro y edición jugador con presentación profesional, pago seguro y atención directa por WhatsApp.</p>
            <div class="hero-trust">
                <span>Calidad 1.1</span>
                <span>Envío nacional</span>
                <span>Pago contra entrega</span>
            </div>
            <div class="hero-buttons">
                <a class="btn btn-primary" href="/productos">Ver colección</a>
                <a class="btn btn-ghost" href="https://wa.me/573168930015">Comprar por WhatsApp</a>
            </div>
        </div>
        <div class="hero-gallery">
            @forelse($heroProducts as $product)
                @php $image = optional($product->images->firstWhere('is_primary', true) ?: $product->images->first())->url; @endphp
                <article class="hero-product">
                    <img src="{{ $image ?: $fallbackImages[$loop->index % count($fallbackImages)] }}" alt="{{ $product->name }}">
                    <strong>{{ $product->name }}</strong>
                </article>
            @empty
                @foreach($fallbackImages as $img)
                    <article class="hero-product"><img src="{{ $img }}" alt="Camiseta premium"><strong>Camisetas premium 1.1</strong></article>
                @endforeach
            @endforelse
        </div>
    </section>

    <main>
        <section class="section-wrap">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Compra rápido</span>
                    <h2>Categorías principales</h2>
                </div>
                <a href="/productos">Ver todo</a>
            </div>
            <div class="category-grid">
                @forelse($categories as $category)
                    <a class="category-card" href="/productos?category={{ $category->slug }}">
                        <img src="{{ $category->image_url ?: asset('grancamerino/assets/logo.png') }}" alt="{{ $category->name }}">
                        <span>{{ $category->name }}</span>
                    </a>
                @empty
                    @foreach(['Clubes europeos','Selecciones','Edición jugador','Retro','Kits','Ofertas'] as $category)
                    <a class="category-card" href="/productos"><img src="{{ asset('grancamerino/assets/logo.png') }}" alt="{{ $category }}"><span>{{ $category }}</span></a>
                    @endforeach
                @endforelse
            </div>
        </section>

        <section class="section-wrap products-section">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Catálogo real</span>
                    <h2>Productos destacados</h2>
                </div>
                <a href="/productos">Ver todos</a>
            </div>
            <div class="product-grid">
                @forelse($products as $product)
                    @php
                        $image = optional($product->images->firstWhere('is_primary', true) ?: $product->images->first())->url;
                        $price = $product->price_cop ?: $product->price_usd;
                    @endphp
                    <article class="product-card">
                        <a class="product-image" href="/productos/{{ $product->slug }}">
                            <img src="{{ $image ?: asset('grancamerino/assets/logo.png') }}" alt="{{ $product->name }}" loading="lazy">
                            <span>Nuevo</span>
                        </a>
                        <div class="product-info">
                            <a href="/productos/{{ $product->slug }}"><h3>{{ $product->name }}</h3></a>
                            <p>{{ optional($product->team)->name ?: optional($product->category)->name ?: 'Camiseta 1.1' }}</p>
                            <div class="product-price">
                                <strong>${{ number_format((float)$price, 0, ',', '.') }}</strong>
                                <button>Agregar</button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Cuando conectes la base de datos, aquí aparecerán tus productos reales automáticamente.</div>
                @endforelse
            </div>
        </section>

        <section class="benefits">
            <div><strong>Envíos gratis</strong><span>Por compras superiores a $350.000</span></div>
            <div><strong>Pago contra entrega</strong><span>Disponible en Colombia</span></div>
            <div><strong>Cambios gratuitos</strong><span>Si no estás satisfecho</span></div>
            <div><strong>Atención 24/7</strong><span>Por WhatsApp</span></div>
        </section>

        <section class="promo-band">
            <div>
                <span class="eyebrow">Oferta exclusiva</span>
                <h2>Hasta 15% OFF en camisetas seleccionadas</h2>
                <p>Activa promociones reales desde tu panel sin tocar pagos ni base de datos.</p>
            </div>
            <a class="btn btn-primary" href="/productos?ofertas=1">Comprar ahora</a>
        </section>

        <section class="section-wrap testimonials">
            <div class="section-heading"><div><span class="eyebrow">Confianza</span><h2>Lo que dicen nuestros clientes</h2></div></div>
            <div class="testimonial-grid">
                <article>★★★★★<p>La calidad se siente premium. Volveré a comprar sin duda.</p><strong>Juan Camilo R.</strong><span>Medellín</span></article>
                <article>★★★★★<p>Llegó rápido y la camiseta es tal cual como la muestran.</p><strong>Santiago M.</strong><span>Bogotá</span></article>
                <article>★★★★★<p>Excelente atención y productos muy bien presentados.</p><strong>Andrés F.</strong><span>Cali</span></article>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-brand"><img src="{{ asset('grancamerino/assets/logo.png') }}" alt="El Gran Camerino 33"><p>Tu tienda número 1 de camisetas 1.1 en Colombia.</p></div>
        <div><h4>Información</h4><a href="/nosotros">Sobre nosotros</a><a href="/terminos">Términos</a><a href="/politicas">Privacidad</a></div>
        <div><h4>Mi cuenta</h4><a href="/login">Mi cuenta</a><a href="/pedidos">Mis pedidos</a><a href="/favoritos">Favoritos</a></div>
        <div><h4>Contacto</h4><a href="https://wa.me/573168930015">WhatsApp: 316 693 0015</a><span>Medellín, Colombia</span><strong>Visa · Mastercard · PSE</strong></div>
    </footer>
    <a class="whatsapp" href="https://wa.me/573168930015" target="_blank" rel="noopener">WhatsApp</a>
</body>
</html>
