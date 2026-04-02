<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use App\Repositories\InventoryBatchRepository;
use App\Repositories\ProductRepository;
use App\Validation\InventoryBatchValidator;

class InventoryBatchController
{
    private InventoryBatchRepository $batchRepo;

    private ProductRepository $productRepo;

    public function __construct()
    {
        $this->batchRepo = new InventoryBatchRepository();
        $this->productRepo = new ProductRepository();
    }

    public function index(Request $request): void
    {
        $shopId = $this->requireShopId();
        $page = max(1, (int) ($request->query['pagina'] ?? 1));
        $productId = isset($request->query['producto_id']) && $request->query['producto_id'] !== ''
            ? (int) $request->query['producto_id']
            : null;
        if ($productId !== null && $productId <= 0) {
            $productId = null;
        }
        $q = trim((string) ($request->query['q'] ?? ''));
        $vencimiento = (string) ($request->query['vencimiento'] ?? 'todos');
        if (!in_array($vencimiento, ['todos', 'proximos_30', 'vencidos', 'sin_fecha'], true)) {
            $vencimiento = 'todos';
        }

        $result = $this->batchRepo->listByShop($shopId, $page, $productId, $q, $vencimiento);
        $products = $this->productRepo->listActiveForSelect($shopId);

        View::render('admin/lotes/indice', [
            'result' => $result,
            'filters' => [
                'producto_id' => $productId,
                'q' => $q,
                'vencimiento' => $vencimiento,
            ],
            'products' => $products,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
        ]);
    }

    public function create(Request $request): void
    {
        $shopId = $this->requireShopId();
        $products = $this->productRepo->listActiveForSelect($shopId);
        View::render('admin/lotes/crear', [
            'products' => $products,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $shopId = $this->requireShopId();
        $body = $request->body;
        $productId = (int) ($body['product_id'] ?? 0);
        $lotCode = trim((string) ($body['lot_code'] ?? ''));
        $quantity = (string) ($body['quantity'] ?? '');
        $expiry = InventoryBatchValidator::normalizedExpiry(isset($body['expiry_date']) ? (string) $body['expiry_date'] : null);
        $notes = trim((string) ($body['notes'] ?? ''));
        $notes = $notes === '' ? null : $notes;

        $old = [
            'product_id' => $productId,
            'lot_code' => $lotCode,
            'quantity' => $quantity,
            'expiry_date' => $expiry ?? '',
            'notes' => $notes ?? '',
        ];

        $errors = InventoryBatchValidator::validate([
            'product_id' => $productId,
            'lot_code' => $lotCode,
            'quantity' => $quantity,
            'expiry_date' => $expiry ?? '',
            'notes' => $notes ?? '',
        ]);

        $product = $productId > 0 ? $this->productRepo->findById($productId, $shopId) : null;
        if (!$product || ($product['status'] ?? '') !== 'ACTIVE') {
            $errors[] = 'El producto no es válido o está inactivo.';
        }

        if ($errors === [] && $this->batchRepo->existsLotForProduct($shopId, $productId, $lotCode)) {
            $errors[] = 'Ya existe un lote con ese código para el mismo producto.';
        }

        if ($errors !== []) {
            $this->renderCrear($errors, $old);
            return;
        }

        $this->batchRepo->create(
            $shopId,
            $productId,
            $lotCode,
            (float) $quantity,
            $expiry,
            $notes
        );
        Flash::set('success', 'Lote registrado correctamente.');
        Redirect::to('/admin/lotes');
    }

    public function edit(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        $batch = $id ? $this->batchRepo->findById($id, $shopId) : null;
        if (!$batch) {
            Flash::set('danger', 'Lote no encontrado.');
            Redirect::to('/admin/lotes');
        }
        $products = $this->mergeProductOptionIfMissing(
            $this->productRepo->listActiveForSelect($shopId),
            (int) ($batch['product_id'] ?? 0),
            $shopId
        );
        View::render('admin/lotes/editar', [
            'batch' => $batch,
            'products' => $products,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function update(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        $batch = $id ? $this->batchRepo->findById($id, $shopId) : null;
        if (!$batch) {
            Flash::set('danger', 'Lote no encontrado.');
            Redirect::to('/admin/lotes');
        }

        $body = $request->body;
        $productId = (int) ($body['product_id'] ?? 0);
        $lotCode = trim((string) ($body['lot_code'] ?? ''));
        $quantity = (string) ($body['quantity'] ?? '');
        $expiry = InventoryBatchValidator::normalizedExpiry(isset($body['expiry_date']) ? (string) $body['expiry_date'] : null);
        $notes = trim((string) ($body['notes'] ?? ''));
        $notes = $notes === '' ? null : $notes;

        $old = [
            'product_id' => $productId,
            'lot_code' => $lotCode,
            'quantity' => $quantity,
            'expiry_date' => $expiry ?? '',
            'notes' => $notes ?? '',
        ];

        $errors = InventoryBatchValidator::validate([
            'product_id' => $productId,
            'lot_code' => $lotCode,
            'quantity' => $quantity,
            'expiry_date' => $expiry ?? '',
            'notes' => $notes ?? '',
        ]);

        $product = $productId > 0 ? $this->productRepo->findById($productId, $shopId) : null;
        if (!$product || ($product['status'] ?? '') !== 'ACTIVE') {
            $errors[] = 'El producto no es válido o está inactivo.';
        }

        if ($errors === [] && $this->batchRepo->existsLotForProduct($shopId, $productId, $lotCode, $id)) {
            $errors[] = 'Ya existe otro lote con ese código para el mismo producto.';
        }

        if ($errors !== []) {
            $this->renderEditar($batch, $errors, $old);
            return;
        }

        $this->batchRepo->update($id, $shopId, $productId, $lotCode, (float) $quantity, $expiry, $notes);
        Flash::set('success', 'Lote actualizado correctamente.');
        Redirect::to('/admin/lotes');
    }

    public function delete(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($this->batchRepo->delete($id, $shopId)) {
            Flash::set('success', 'Lote eliminado.');
        } else {
            Flash::set('danger', 'No se pudo eliminar el lote.');
        }
        Redirect::to('/admin/lotes');
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    private function mergeProductOptionIfMissing(array $products, int $productId, int $shopId): array
    {
        if ($productId <= 0) {
            return $products;
        }
        foreach ($products as $p) {
            if ((int) ($p['id'] ?? 0) === $productId) {
                return $products;
            }
        }
        $p = $this->productRepo->findById($productId, $shopId);
        if ($p) {
            $label = (string) ($p['name'] ?? '');
            if (($p['status'] ?? '') !== 'ACTIVE') {
                $label .= ' (inactivo)';
            }
            array_unshift($products, [
                'id' => $productId,
                'name' => $label,
                'sku' => $p['sku'] ?? null,
            ]);
        }

        return $products;
    }

    private function requireShopId(): int
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }

        return $shopId;
    }

    private function getUserName(): string
    {
        $stmt = Database::pdo()->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::userId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

        return $name !== '' ? $name : 'Usuario';
    }

    private function getShopName(): string
    {
        $stmt = Database::pdo()->prepare('SELECT name FROM shops WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::shopId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (string) ($row['name'] ?? '');
    }

    /**
     * @param array<string, mixed> $old
     */
    private function renderCrear(array $errors, array $old): void
    {
        $shopId = Auth::shopId();
        $products = $shopId !== null
            ? $this->mergeProductOptionIfMissing(
                $this->productRepo->listActiveForSelect($shopId),
                (int) ($old['product_id'] ?? 0),
                $shopId
            )
            : [];
        View::render('admin/lotes/crear', [
            'products' => $products,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => null,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    /**
     * @param array<string, mixed> $batch
     * @param array<string, mixed> $old
     */
    private function renderEditar(array $batch, array $errors, array $old): void
    {
        $shopId = Auth::shopId();
        $pid = (int) ($old['product_id'] ?? $batch['product_id'] ?? 0);
        $products = $shopId !== null
            ? $this->mergeProductOptionIfMissing($this->productRepo->listActiveForSelect($shopId), $pid, $shopId)
            : [];
        View::render('admin/lotes/editar', [
            'batch' => $batch,
            'products' => $products,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => null,
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}
