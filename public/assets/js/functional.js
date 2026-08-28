document.querySelectorAll('[data-table-search]').forEach(input=>input.addEventListener('input',()=>{const query=input.value.toLocaleLowerCase();document.querySelectorAll('[data-search-row]').forEach(row=>row.hidden=!row.textContent.toLocaleLowerCase().includes(query))}));

const publicSearch=document.querySelector('.site-search input');
publicSearch?.addEventListener('input',()=>{const query=publicSearch.value.toLocaleLowerCase();document.querySelectorAll('.training-card').forEach(card=>card.hidden=!card.textContent.toLocaleLowerCase().includes(query))});

document.querySelectorAll('.site-filters input').forEach(filter=>filter.addEventListener('change',()=>{const selected=[...document.querySelectorAll('.site-filters input:checked')].map(input=>input.parentElement.textContent.trim().toLocaleLowerCase());document.querySelectorAll('.training-card').forEach(card=>{card.hidden=selected.length>1&&!selected.some(value=>value.includes('toutes')||card.textContent.toLocaleLowerCase().includes(value.replace(/\d+/g,'').trim()))})}));

document.querySelectorAll('.shop-section .filter-chips button').forEach((button,index)=>button.addEventListener('click',()=>{button.parentElement.querySelectorAll('button').forEach(item=>item.classList.toggle('active',item===button));document.querySelectorAll('.product-card').forEach((card,cardIndex)=>card.hidden=index>0&&cardIndex%4!==index-1)}));

document.querySelectorAll('.program-line').forEach(line=>line.addEventListener('click',()=>line.classList.toggle('expanded')));

document.querySelector('.lesson-nav .primary')?.addEventListener('click',event=>{event.currentTarget.textContent='✓ Leçon terminée';event.currentTarget.disabled=true;const progress=document.querySelector('.learn-progress .progress span');if(progress)progress.style.width='76%'});
