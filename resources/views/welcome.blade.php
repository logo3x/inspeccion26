<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Experticios BGA · Inspeccion26</title>
    <style>
        :root {
            --bg: #0a0a0a;
            --bg-elev: #141414;
            --fg: #f5f5f5;
            --muted: #9ca3af;
            --accent: #f59e0b;
            --accent-dark: #b45309;
            --border: #262626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(ellipse at top, #1a1a1a 0%, var(--bg) 60%);
            color: var(--fg);
            font-family: -apple-system, system-ui, "Segoe UI", Roboto, sans-serif;
            display: flex;
            flex-direction: column;
        }
        header, footer { padding: 1.25rem 2rem; }
        header { border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: .75rem; font-weight: 600; letter-spacing: .02em; }
        .brand .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 12px var(--accent); }
        .nav-btn {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .55rem 1rem;
            background: var(--accent); color: #1a1a1a;
            border-radius: 8px; text-decoration: none; font-weight: 600;
            transition: background .15s;
        }
        .nav-btn:hover { background: var(--accent-dark); color: #fff; }

        main {
            flex: 1;
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            padding: 4rem 2rem 3rem;
        }
        .hero { text-align: center; margin-bottom: 3rem; }
        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.25rem);
            line-height: 1.1;
            margin: 0 0 1rem;
            font-weight: 700;
            letter-spacing: -.02em;
        }
        .hero h1 span { color: var(--accent); }
        .hero p {
            color: var(--muted);
            font-size: 1.1rem;
            max-width: 720px;
            margin: 0 auto 2rem;
            line-height: 1.55;
        }
        .cta-group { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
        .cta-group a {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .85rem 1.5rem;
            border-radius: 8px; text-decoration: none; font-weight: 600;
            transition: transform .1s, background .15s;
        }
        .cta-primary { background: var(--accent); color: #1a1a1a; }
        .cta-primary:hover { background: var(--accent-dark); color: #fff; }
        .cta-secondary { border: 1px solid var(--border); color: var(--fg); }
        .cta-secondary:hover { border-color: var(--accent); }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1rem;
            margin-top: 3.5rem;
        }
        .feature {
            background: var(--bg-elev);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .feature .ico {
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(245, 158, 11, .15);
            color: var(--accent);
            border-radius: 8px;
            font-size: 1.25rem;
            margin-bottom: .75rem;
        }
        .feature h3 { margin: 0 0 .4rem; font-size: 1.05rem; }
        .feature p { margin: 0; color: var(--muted); font-size: .9rem; line-height: 1.5; }

        footer {
            color: var(--muted);
            font-size: .8rem;
            text-align: center;
            border-top: 1px solid var(--border);
        }
        footer strong { color: var(--fg); }
    </style>
</head>
<body>
    <header>
        <div class="brand">
            <span class="dot"></span>
            <span>Experticios BGA</span>
        </div>
        <a class="nav-btn" href="/admin">Acceder al panel →</a>
    </header>

    <main>
        <section class="hero">
            <h1>Sistema de <span>fichas técnicas vehiculares</span></h1>
            <p>
                Plataforma administrativa para el inventario, valoración física y trazabilidad
                de bienes vehiculares en abandono según la Ley 1730 de 2014. Importación masiva
                desde Excel, generación documental DOCX y control de estados auditado.
            </p>
            <div class="cta-group">
                <a class="cta-primary" href="/admin">Entrar al panel administrativo</a>
                <a class="cta-secondary" href="mailto:contacto@experticiosbga.com">Contacto</a>
            </div>
        </section>

        <section class="features">
            <div class="feature">
                <div class="ico">📋</div>
                <h3>Fichas técnicas</h3>
                <p>Captura completa de datos del vehículo, propietario y proceso de inmovilización con seguimiento de estado.</p>
            </div>
            <div class="feature">
                <div class="ico">📊</div>
                <h3>Importación masiva</h3>
                <p>Carga miles de registros desde Excel con preview, validación por fila y bitácora de errores.</p>
            </div>
            <div class="feature">
                <div class="ico">📄</div>
                <h3>Generación documental</h3>
                <p>Fichas técnicas en formato Word listas para imprimir, descarga individual o lote ZIP.</p>
            </div>
            <div class="feature">
                <div class="ico">🛡️</div>
                <h3>Auditoría y permisos</h3>
                <p>Registro completo de cambios, control de acceso por rol y trazabilidad institucional.</p>
            </div>
        </section>
    </main>

    <footer>
        <p><strong>Experticios BGA</strong> · Sistema interno de inspección vehicular · Acceso restringido a personal autorizado</p>
    </footer>
</body>
</html>
