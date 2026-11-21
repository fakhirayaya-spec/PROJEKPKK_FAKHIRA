<style>
    .so-brown { color: #3d2314; }
    .so-card { border-radius: 16px; border: 1px solid #efe8e0; box-shadow: 0 2px 8px rgba(61, 35, 20, .04); background: #fff; }
    .so-stat { display: flex; align-items: center; gap: 12px; padding: 14px 16px; }
    .so-stat .ic { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .so-stat .ic.brown { background: #f5ebe0; color: #8a5a2b; }
    .so-stat .ic.amber { background: #fef3c7; color: #b45309; }
    .so-stat .ic.red { background: #fee2e2; color: #dc2626; }
    .so-stat .lbl { font-size: 12px; color: #8b7d70; }
    .so-stat .val { font-size: 1.35rem; font-weight: 800; color: #3d2314; line-height: 1.1; }

    .so-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(235px, 1fr)); gap: 12px; }
    .so-tile { border: 1px solid #efe8e0; border-radius: 14px; padding: 12px; background: #fffdfa; display: flex; flex-direction: column; gap: 8px; }
    .so-tile.habis { border-color: #fecaca; background: #fff8f8; }
    .so-tile.menipis { border-color: #fde68a; background: #fffcf2; }
    .so-tile-head { display: flex; align-items: center; gap: 10px; }
    .so-tile-head .nm { font-weight: 700; color: #3d2314; line-height: 1.2; }
    .so-tile-head .rule { font-size: 11px; color: #9a8c7e; line-height: 1.3; }
    .so-thumb { width: 52px; height: 52px; border-radius: 12px; flex-shrink: 0; background: #f7efe4; color: #b08a63; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #efe8e0; }
    .so-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .so-thumb.sm { width: 40px; height: 40px; font-size: 1.15rem; border-radius: 10px; }
    .so-num { display: flex; align-items: baseline; justify-content: space-between; }
    .so-num .n { font-size: 1.6rem; font-weight: 800; color: #3d2314; line-height: 1; }
    .so-num .u { font-size: 12px; color: #8b7d70; font-weight: 500; margin-left: 3px; }

    .so-badge { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; }
    .so-badge-aman { background: #dcfce7; color: #16a34a; }
    .so-badge-menipis { background: #fef3c7; color: #b45309; }
    .so-badge-habis { background: #fee2e2; color: #dc2626; }

    .so-bar { height: 6px; border-radius: 6px; background: #f1ebe3; overflow: hidden; }
    .so-bar > span { display: block; height: 100%; border-radius: 6px; }
    .so-bar .aman { background: #22c55e; }
    .so-bar .menipis { background: #f59e0b; }
    .so-bar .habis { background: #ef4444; }

    .so-refill { display: flex; gap: 6px; align-items: center; margin-top: auto; padding-top: 8px; border-top: 1px dashed #efe8e0; }
    .so-refill input { width: 78px; }
    .so-refill .unit { font-size: 12px; color: #8b7d70; white-space: nowrap; }
    .so-hint { font-size: 11px; color: #9a8c7e; }

    .so-btn { background: linear-gradient(90deg, #fcae1e 0%, #f7931e 100%); color: #fff; border: none; font-weight: 700; }
    .so-btn:hover { color: #fff; opacity: .9; }
</style>
<script>
    // Kalau file gambar bahan tidak ditemukan, tampilkan ikon bawaan (bukan gambar rusak)
    function soGagal(img) {
        var p = img.parentNode;
        if (p) { p.innerHTML = '<i class="bi bi-box-seam"></i>'; }
    }
</script>
