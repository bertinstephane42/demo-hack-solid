<div class="text-center mb-3">
    <a href="<?= route('home') ?>" class="btn btn-outline-secondary">Retour à l'accueil</a>
</div>
<div class="container mt-3">
    <button class="back-btn" id="backBtn" style="display:none;">← Retour</button>
    <div class="menu-frame-outer mt-3">
        <div class="menu-frame glass-panel">
            <div id="menuContainer" class="card-grid"></div>
        </div>
    </div>
    <div id="legendBox">
        <h3 style="text-align:center; margin-bottom:15px; font-size:1.25rem; font-weight:600;">Légende des boutons</h3>
        <div class="legend-row"><button class="legend-btn legend-category">Catégorie</button><span>Catégorie ou sous-catégorie du site</span></div>
        <div class="legend-row"><button class="legend-btn legend-proxy">Proxy</button><span>Ressource web protégée par proxy PHP / DokuWiki</span></div>
        <div class="legend-row"><button class="legend-btn legend-doku">DokuWiki</button><span>Ressource hébergée sur DokuWiki</span></div>
        <div class="legend-row"><button class="legend-btn legend-site">Interne</button><span>Ressource web interne hébergée sur le site</span></div>
        <div class="legend-row"><button class="legend-btn legend-external">Externe</button><span>Ressource web externe n'appartenant pas au site</span></div>
    </div>
</div>
<script>
const siteMenu = <?= $menu->toJson() ?>;
let historyStack = [];
function renderMenu(level, key=null){
    const container = document.getElementById('menuContainer');
    container.innerHTML = '';
    const backBtn = document.getElementById('backBtn');
    if(level === 'categories'){
        backBtn.style.display = 'inline-block'; backBtn.textContent = 'Racine de DokuWiki'; backBtn.disabled = true; backBtn.dataset.home = 'true';
    } else {
        backBtn.style.display = 'inline-block'; backBtn.textContent = '← Retour'; backBtn.disabled = false; backBtn.dataset.home = 'false';
    }
    if(level === 'categories'){
        for(const cat in siteMenu){
            const div = document.createElement('div');
            const subcats = siteMenu[cat]['subcategories'] || {};
            const items = siteMenu[cat]['items'] || [];
            const hasClickableSub = Object.keys(subcats).some(sub => (subcats[sub]['items'] && subcats[sub]['items'].length > 0));
            const hasClickableItem = items.some(item => item.url && item.url.trim() !== '');
            const isClickable = hasClickableSub || hasClickableItem;
            div.className = isClickable ? 'feature-card' : 'feature-card-disabled';
            div.dataset.type = 'category'; div.dataset.key = cat; div.textContent = cat;
            container.appendChild(div);
        }
    } else if(level === 'subcategories'){
        backBtn.style.display = 'inline-block';
        const subcats = siteMenu[key]['subcategories'] || {};
        for(const sub in subcats){
            const div = document.createElement('div');
            const items = subcats[sub]['items'] || [];
            const isClickable = items.some(item => item.url && item.url.trim() !== '');
            div.className = isClickable ? 'feature-card' : 'feature-card-disabled';
            div.dataset.type = 'subcategory'; div.dataset.key = sub; div.dataset.parent = key; div.textContent = sub;
            container.appendChild(div);
        }
        const directItems = siteMenu[key]['items'] || [];
        directItems.forEach(item=>{
            const div = document.createElement('div');
            div.className = 'feature-card'; div.dataset.type = 'item'; div.dataset.url = item.url; div.textContent = item.label;
            classifyButton(div, item.url); container.appendChild(div);
        });
    } else if(level === 'items'){
        backBtn.style.display = 'inline-block';
        const cat = key.parent; const sub = key.key;
        const items = siteMenu[cat]['subcategories'][sub]['items'] || [];
        items.forEach(item=>{
            const div = document.createElement('div');
            const isClickable = item.url && item.url.trim() !== '';
            div.className = isClickable ? 'feature-card' : 'feature-card-disabled';
            div.dataset.type = 'item'; if(isClickable) div.dataset.url = item.url; div.textContent = item.label;
            classifyButton(div, item.url); container.appendChild(div);
        });
    }
    const legend = document.getElementById('legendBox');
    legend.classList.remove('show'); legend.style.display = 'block'; void legend.offsetWidth;
    setTimeout(() => { legend.classList.add('show'); }, 200);
}
document.getElementById('menuContainer').addEventListener('click', e=>{
    const target = e.target.closest('.feature-card'); if(!target) return;
    if(target.classList.contains('feature-card-disabled')) return;
    const type = target.dataset.type;
    if(type === 'category'){ historyStack.push({level:'categories'}); renderMenu('subcategories', target.dataset.key); historyStack.push({level:'subcategories', key: target.dataset.key}); }
    else if(type === 'subcategory'){ renderMenu('items', {parent: target.dataset.parent, key: target.dataset.key}); historyStack.push({level:'items', key: target.dataset.key, parent: target.dataset.parent}); }
    else if(type === 'item'){ window.open(target.dataset.url, '_blank'); }
});
document.getElementById('backBtn').addEventListener('click', ()=>{
    const backBtn = document.getElementById('backBtn');
    if(backBtn.dataset.home === 'true'){ if(confirm("Voulez-vous vraiment retourner à l'accueil ?")){ window.location.href = '/'; } return; }
    historyStack.pop(); const last = historyStack.pop();
    if(!last){ renderMenu('categories'); historyStack = []; }
    else if(last.level === 'categories'){ renderMenu('categories'); historyStack = []; }
    else if(last.level === 'subcategories'){ renderMenu('subcategories', last.key); historyStack.push(last); }
});
function classifyButton(div, url){
    if(!url) return;
    if(url.includes('/proxy_user.php?tp=')) div.classList.add('item-proxy');
    else if(url.includes('/doku.php/')) div.classList.add('item-doku');
    else if(url.includes('cours-reseaux.fr')) div.classList.add('item-site');
    else div.classList.add('item-external');
}
function updateLegendBoxPosition() {
    const legendBox = document.getElementById('legendBox'); const menuContainer = document.getElementById('menuContainer'); const footer = document.querySelector('footer');
    if (!legendBox || !menuContainer || !footer) return;
    const scrollY = window.scrollY || window.pageYOffset;
    const menuRect = menuContainer.getBoundingClientRect(); const menuBottom = menuRect.bottom + scrollY;
    const footerRect = footer.getBoundingClientRect(); const footerTop = footerRect.top + scrollY;
    const top = menuBottom + 80; const maxHeight = footerTop - top - 20;
    const viewportWidth = window.innerWidth; let width = Math.min(viewportWidth * 0.9, 605); width = Math.max(width, 200);
    legendBox.style.top = top + 'px'; legendBox.style.maxHeight = maxHeight + 'px'; legendBox.style.width = width + 'px';
}
renderMenu('categories'); updateLegendBoxPosition();
window.addEventListener('resize', updateLegendBoxPosition); window.addEventListener('scroll', updateLegendBoxPosition);
</script>
