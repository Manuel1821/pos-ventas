<?php
$errors = $errors ?? [];
$old = $old ?? [];
$giros = $giros ?? [];
$shopSlug = $shopSlug ?? '';
$shopProfileMigrationNeeded = $shopProfileMigrationNeeded ?? false;
$shopTicketMigrationNeeded = $shopTicketMigrationNeeded ?? false;
$shopQuotationMigrationNeeded = $shopQuotationMigrationNeeded ?? false;
$basePath = $basePath ?? '';
ob_start();
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= htmlspecialchars($basePath . '/admin/dashboard', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h4 mb-0"><i class="bi bi-gear me-2" style="color:var(--teal);"></i> Configuración de la tienda</h1>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card border-0 card-shadow rounded-4">
            <div class="card-body p-4">
                <p class="text-muted mb-4">Estos datos identifican tu negocio en el sistema y pueden usarse en tickets, reportes y comunicación.</p>
                <?php if (!empty($shopProfileMigrationNeeded)): ?>
                    <div class="alert alert-warning">
                        <strong>Falta actualizar la base de datos.</strong> En este servidor aún no existen las columnas de configuración extendida. Puedes editar el nombre de la tienda; el resto de campos no se guardará hasta aplicar la migración.
                        Entra a <a href="<?= htmlspecialchars($basePath . '/setup', ENT_QUOTES, 'UTF-8') ?>" class="alert-link">/setup</a>, escribe <strong>SI</strong> y confirma, o ejecuta en MySQL el script <code>database/sql/hito11_shop_settings.sql</code>.
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 list-unstyled">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form action="<?= htmlspecialchars($basePath . '/admin/configuracion/tienda', ENT_QUOTES, 'UTF-8') ?>" method="post">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Nombre de la tienda <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="150" autofocus>
                            <?php if ($shopSlug !== ''): ?>
                                <div class="form-text">Identificador del catálogo web: <code><?= htmlspecialchars($shopSlug, ENT_QUOTES, 'UTF-8') ?></code> (ruta <code>/catalogo/<?= htmlspecialchars($shopSlug, ENT_QUOTES, 'UTF-8') ?></code>)</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label for="responsible_name" class="form-label">Nombre de la persona responsable</label>
                            <input type="text" class="form-control" id="responsible_name" name="responsible_name" value="<?= htmlspecialchars($old['responsible_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="200" placeholder="Opcional">
                        </div>
                        <div class="col-12 col-lg-6">
                            <label for="rfc" class="form-label">RFC</label>
                            <input type="text" class="form-control text-uppercase" id="rfc" name="rfc" value="<?= htmlspecialchars($old['rfc'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="20" placeholder="Opcional" autocomplete="off">
                        </div>
                        <div class="col-12 col-lg-6">
                            <label for="contact_email" class="form-label">Correo de la tienda</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= htmlspecialchars($old['contact_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="190" placeholder="contacto@ejemplo.com">
                        </div>
                        <div class="col-12 col-lg-6">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="30" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($old['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="500" placeholder="Calle, número, colonia, ciudad…">
                        </div>
                        <div class="col-12">
                            <label for="business_type" class="form-label">Giro</label>
                            <select class="form-select" id="business_type" name="business_type" style="max-height: 240px;">
                                <option value="" <?= (($old['business_type'] ?? '') === '') ? 'selected' : '' ?>>Selecciona de la lista</option>
                                <?php foreach ($giros as $g): ?>
                                    <option value="<?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?>" <?= (($old['business_type'] ?? '') === $g) ? 'selected' : '' ?>><?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Tipo de negocio o rubro principal.</div>
                        </div>
                    </div>

                    <h2 class="h6 fw-semibold mt-4 mb-3"><i class="bi bi-printer me-2" style="color:var(--teal);"></i> Impresión de tickets</h2>
                    <?php if (!empty($shopTicketMigrationNeeded)): ?>
                        <div class="alert alert-warning">
                            Para guardar el ancho de papel y la fuente del ticket, aplica la migración en el servidor (por ejemplo <a href="<?= htmlspecialchars($basePath . '/setup', ENT_QUOTES, 'UTF-8') ?>" class="alert-link">/setup</a> con <strong>SI</strong>) o ejecuta en MySQL el script <code>database/sql/hito13_ticket_print.sql</code>.
                        </div>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="ticket_paper_width_mm" class="form-label">Ancho del papel</label>
                            <select class="form-select" id="ticket_paper_width_mm" name="ticket_paper_width_mm" <?= !empty($shopTicketMigrationNeeded) ? 'disabled' : '' ?>>
                                <?php
                                $tw = (int) ($old['ticket_paper_width_mm'] ?? 80);
                                foreach ([58 => '58 mm (ticket angosto)', 72 => '72 mm', 80 => '80 mm (estándar)'] as $mm => $label):
                                ?>
                                    <option value="<?= (int) $mm ?>" <?= $tw === $mm ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($shopTicketMigrationNeeded)): ?>
                                <input type="hidden" name="ticket_paper_width_mm" value="<?= (int) ($old['ticket_paper_width_mm'] ?? 80) ?>">
                            <?php endif; ?>
                            <div class="form-text">El ticket se limita a este ancho para que encaje en la impresora.</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="ticket_font_preset" class="form-label">Tipo de fuente</label>
                            <select class="form-select" id="ticket_font_preset" name="ticket_font_preset" <?= !empty($shopTicketMigrationNeeded) ? 'disabled' : '' ?>>
                                <?php
                                $tp = (string) ($old['ticket_font_preset'] ?? 'sans_bold');
                                $presets = [
                                    'sans_bold' => 'Sans — más gruesa (recomendada)',
                                    'sans' => 'Sans — normal',
                                    'system' => 'Sistema (según navegador)',
                                    'mono_bold' => 'Monoespaciada — más gruesa',
                                    'mono' => 'Monoespaciada — normal',
                                    'serif' => 'Serif (con remates)',
                                ];
                                foreach ($presets as $val => $label):
                                ?>
                                    <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" <?= $tp === $val ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($shopTicketMigrationNeeded)): ?>
                                <input type="hidden" name="ticket_font_preset" value="<?= htmlspecialchars((string) ($old['ticket_font_preset'] ?? 'sans_bold'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="ticket_font_size_pt" class="form-label">Tamaño base (pt)</label>
                            <input type="number" class="form-control" id="ticket_font_size_pt" name="ticket_font_size_pt" min="8" max="24" step="0.5" value="<?= htmlspecialchars((string) ($old['ticket_font_size_pt'] ?? 13), ENT_QUOTES, 'UTF-8') ?>" <?= !empty($shopTicketMigrationNeeded) ? 'disabled' : '' ?>>
                            <?php if (!empty($shopTicketMigrationNeeded)): ?>
                                <input type="hidden" name="ticket_font_size_pt" value="<?= htmlspecialchars((string) ($old['ticket_font_size_pt'] ?? 13), ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                            <div class="form-text">Entre 8 y 24 pt. Sube el valor si el texto sale muy pequeño al imprimir.</div>
                        </div>
                    </div>

                    <h2 class="h6 fw-semibold mt-4 mb-3" id="cotizacion-impresion"><i class="bi bi-file-earmark-richtext me-2" style="color:var(--teal);"></i> Impresión avanzada (cotizaciones)</h2>
                    <?php if (!empty($shopQuotationMigrationNeeded)): ?>
                        <div class="alert alert-warning">
                            Para guardar el formato de cotización (PDF desde el navegador), aplica la migración en el servidor (por ejemplo <a href="<?= htmlspecialchars($basePath . '/setup', ENT_QUOTES, 'UTF-8') ?>" class="alert-link">/setup</a> con <strong>SI</strong>) o ejecuta en MySQL el script <code>database/sql/hito17_quotation_print.sql</code>.
                        </div>
                    <?php endif; ?>
                    <p class="text-muted small mb-3">Define cómo se verá el documento al usar <strong>Imprimir / PDF</strong> en una cotización. No afecta a los tickets del POS.</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="quotation_print_paper" class="form-label">Tamaño de papel</label>
                            <select class="form-select" id="quotation_print_paper" name="quotation_print_paper" <?= !empty($shopQuotationMigrationNeeded) ? 'disabled' : '' ?>>
                                <?php
                                $qpp = strtolower((string) ($old['quotation_print_paper'] ?? 'letter'));
                                ?>
                                <option value="letter" <?= $qpp === 'letter' ? 'selected' : '' ?>>Carta (Letter)</option>
                                <option value="a4" <?= $qpp === 'a4' ? 'selected' : '' ?>>A4</option>
                            </select>
                            <?php if (!empty($shopQuotationMigrationNeeded)): ?>
                                <input type="hidden" name="quotation_print_paper" value="<?= htmlspecialchars((string) ($old['quotation_print_paper'] ?? 'letter'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="quotation_print_margin_mm" class="form-label">Márgenes al imprimir</label>
                            <select class="form-select" id="quotation_print_margin_mm" name="quotation_print_margin_mm" <?= !empty($shopQuotationMigrationNeeded) ? 'disabled' : '' ?>>
                                <?php
                                $qpm = (int) ($old['quotation_print_margin_mm'] ?? 10);
                                foreach ([6 => '6 mm (más contenido)', 8 => '8 mm', 10 => '10 mm (recomendado)', 12 => '12 mm', 15 => '15 mm (más aire)'] as $mm => $label):
                                ?>
                                    <option value="<?= (int) $mm ?>" <?= $qpm === $mm ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($shopQuotationMigrationNeeded)): ?>
                                <input type="hidden" name="quotation_print_margin_mm" value="<?= (int) ($old['quotation_print_margin_mm'] ?? 10) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="quotation_print_scale_pct" class="form-label">Escala del texto</label>
                            <input type="number" class="form-control" id="quotation_print_scale_pct" name="quotation_print_scale_pct" min="85" max="115" step="1" value="<?= (int) ($old['quotation_print_scale_pct'] ?? 100) ?>" <?= !empty($shopQuotationMigrationNeeded) ? 'disabled' : '' ?>>
                            <?php if (!empty($shopQuotationMigrationNeeded)): ?>
                                <input type="hidden" name="quotation_print_scale_pct" value="<?= (int) ($old['quotation_print_scale_pct'] ?? 100) ?>">
                            <?php endif; ?>
                            <div class="form-text">Entre 85% y 115% respecto al tamaño base.</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-check mt-md-4">
                                <?php if (!empty($shopQuotationMigrationNeeded)): ?>
                                    <input type="hidden" name="quotation_print_show_sku" value="<?= (int) ($old['quotation_print_show_sku'] ?? 1) ?>">
                                    <span class="form-check-label text-muted">Mostrar SKU bajo cada producto</span>
                                <?php else: ?>
                                    <input type="hidden" name="quotation_print_show_sku" value="0">
                                    <input class="form-check-input" type="checkbox" name="quotation_print_show_sku" value="1" id="quotation_print_show_sku" <?= ((int) ($old['quotation_print_show_sku'] ?? 1)) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="quotation_print_show_sku">Mostrar SKU bajo cada producto</label>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-check mt-md-4">
                                <?php if (!empty($shopQuotationMigrationNeeded)): ?>
                                    <input type="hidden" name="quotation_print_show_tax_col" value="<?= (int) ($old['quotation_print_show_tax_col'] ?? 1) ?>">
                                    <span class="form-check-label text-muted">Mostrar columna de IVA</span>
                                <?php else: ?>
                                    <input type="hidden" name="quotation_print_show_tax_col" value="0">
                                    <input class="form-check-input" type="checkbox" name="quotation_print_show_tax_col" value="1" id="quotation_print_show_tax_col" <?= ((int) ($old['quotation_print_show_tax_col'] ?? 1)) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="quotation_print_show_tax_col">Mostrar columna de IVA</label>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-check mt-md-4">
                                <?php if (!empty($shopQuotationMigrationNeeded)): ?>
                                    <input type="hidden" name="quotation_print_show_signatures" value="<?= (int) ($old['quotation_print_show_signatures'] ?? 1) ?>">
                                    <span class="form-check-label text-muted">Mostrar líneas de firma</span>
                                <?php else: ?>
                                    <input type="hidden" name="quotation_print_show_signatures" value="0">
                                    <input class="form-check-input" type="checkbox" name="quotation_print_show_signatures" value="1" id="quotation_print_show_signatures" <?= ((int) ($old['quotation_print_show_signatures'] ?? 1)) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="quotation_print_show_signatures">Mostrar líneas de firma</label>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="quotation_print_footer_note" class="form-label">Texto al pie (opcional)</label>
                            <?php if (!empty($shopQuotationMigrationNeeded)): ?>
                                <input type="hidden" name="quotation_print_footer_note" value="<?= htmlspecialchars((string) ($old['quotation_print_footer_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                            <textarea class="form-control" id="quotation_print_footer_note" name="quotation_print_footer_note" rows="2" maxlength="600" placeholder="Ej. Condiciones de pago, datos bancarios o leyenda legal." <?= !empty($shopQuotationMigrationNeeded) ? 'disabled' : '' ?>><?= htmlspecialchars((string) ($old['quotation_print_footer_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">Hasta 600 caracteres. Se muestra debajo del documento al imprimir.</div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        <a href="<?= htmlspecialchars($basePath . '/admin/dashboard', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Volver al panel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card border-0 card-shadow rounded-4 bg-light">
            <div class="card-body p-4">
                <h2 class="h6 fw-semibold mb-3"><i class="bi bi-lightbulb me-1" style="color:var(--teal);"></i> Otros datos útiles más adelante</h2>
                <p class="small text-muted mb-2">Cuando quieras ampliar el POS, suele ayudar registrar también:</p>
                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-2">Horario de atención y días cerrados.</li>
                    <li class="mb-2">Regimen fiscal (para facturación electrónica).</li>
                    <li class="mb-2">Logo o leyenda del ticket (mensaje al pie).</li>
                    <li class="mb-2">Cuentas bancarias o métodos de pago aceptados.</li>
                    <li class="mb-2">Zona horaria y formato de moneda (si operas en varias regiones).</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Configuración de la tienda';
require __DIR__ . '/../../layouts/admin.php';
