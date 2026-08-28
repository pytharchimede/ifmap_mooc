document.querySelectorAll('[data-table-search]').forEach(input=>input.addEventListener('input',()=>{const query=input.value.toLocaleLowerCase();document.querySelectorAll('[data-search-row]').forEach(row=>row.hidden=!row.textContent.toLocaleLowerCase().includes(query))}));

const publicSearch=document.querySelector('.site-search input');
publicSearch?.addEventListener('input',()=>{const query=publicSearch.value.toLocaleLowerCase();document.querySelectorAll('.training-card').forEach(card=>card.hidden=!card.textContent.toLocaleLowerCase().includes(query))});

document.querySelectorAll('.site-filters input').forEach(filter=>filter.addEventListener('change',()=>{const selected=[...document.querySelectorAll('.site-filters input:checked')].map(input=>input.parentElement.textContent.trim().toLocaleLowerCase());document.querySelectorAll('.training-card').forEach(card=>{card.hidden=selected.length>1&&!selected.some(value=>value.includes('toutes')||card.textContent.toLocaleLowerCase().includes(value.replace(/\d+/g,'').trim()))})}));

document.querySelectorAll('.shop-section .filter-chips button').forEach((button,index)=>button.addEventListener('click',()=>{button.parentElement.querySelectorAll('button').forEach(item=>item.classList.toggle('active',item===button));document.querySelectorAll('.product-card').forEach((card,cardIndex)=>card.hidden=index>0&&cardIndex%4!==index-1)}));

document.querySelectorAll('.program-line').forEach(line=>line.addEventListener('click',()=>line.classList.toggle('expanded')));

document.querySelector('.lesson-nav .primary')?.addEventListener('click',event=>{event.currentTarget.textContent='✓ Leçon terminée';event.currentTarget.disabled=true;const progress=document.querySelector('.learn-progress .progress span');if(progress)progress.style.width='76%'});

document.querySelectorAll('form[action$="/admin/cours/supprimer"]').forEach(form=>{const id=form.querySelector('[name=id]')?.value;if(!id)return;const base=location.pathname.split('/admin/')[0];const program=document.createElement('a');program.className='btn secondary';program.textContent='Programme';program.href=`${base}/admin/cours/programme?course=${id}`;const edit=document.createElement('a');edit.className='btn secondary';edit.textContent='Modifier';edit.href=`${base}/admin/cours/modifier?id=${id}`;form.parentElement.prepend(program);form.parentElement.prepend(edit)});

document.querySelectorAll('form.builder-form').forEach(form=>{
  const action=form.querySelector('[name=builder_action]')?.value;
  if(action==='lesson'){
    form.enctype='multipart/form-data';
    const type=form.querySelector('[name=type]');if(type){type.setAttribute('aria-label','Type de contenu');type.title='Choisissez Vidéo, Article ou Ressource';}
    const duration=form.querySelector('[name=duration]');if(duration){duration.placeholder='Durée en minutes';duration.title='Durée estimée de la leçon en minutes';const label=document.createElement('label');label.textContent='Durée (minutes)';label.style.cssText='display:flex;flex-direction:column;gap:5px;font-size:9px;font-weight:700';duration.parentNode.insertBefore(label,duration);label.appendChild(duration);}
    const content=form.querySelector('[name=content]');if(content)content.placeholder='Texte de la leçon, URL vidéo ou URL du document';
    if(content){
      const upload=document.createElement('div');upload.className='lesson-upload full';upload.innerHTML='<label class="upload-label">Ou sélectionner un fichier<input type="file" name="attachment" accept="video/mp4,video/webm,application/pdf,.docx,.pptx,.zip"></label><small>MP4, WebM, PDF, DOCX, PPTX ou ZIP · 150 Mo maximum</small><div class="lesson-live-preview"><em>L’aperçu apparaîtra ici.</em></div>';
      content.insertAdjacentElement('afterend',upload);
      const file=upload.querySelector('input[type=file]'),preview=upload.querySelector('.lesson-live-preview');
      const renderUrl=url=>{const kind=type?.value;if(kind==='video')preview.innerHTML=`<video src="${url}" controls></video>`;else if(kind==='file'&&url.toLowerCase().includes('.pdf'))preview.innerHTML=`<iframe src="${url}"></iframe>`;else preview.innerHTML=url?`<div class="resource-preview">${url}</div>`:'<em>L’aperçu apparaîtra ici.</em>';};
      file.addEventListener('change',()=>{if(file.files[0])renderUrl(URL.createObjectURL(file.files[0]))});
      content.addEventListener('input',()=>renderUrl(content.value.trim()));
      type?.addEventListener('change',()=>{if(file.files[0])renderUrl(URL.createObjectURL(file.files[0]));else renderUrl(content.value.trim())});
    }
  }
  if(action==='assessment'){
    const score=form.querySelector('[name=passing_score]');if(score)score.title='Pourcentage minimum nécessaire pour réussir';
    const attempts=form.querySelector('[name=attempts_allowed]');if(attempts)attempts.title='Nombre maximum de compositions autorisées';
  }
});

document.querySelectorAll('[data-password]').forEach(button=>button.addEventListener('click',()=>{const input=button.previousElementSibling;input.type=input.type==='password'?'text':'password';button.textContent=input.type==='password'?'Afficher':'Masquer'}));
document.querySelector('[data-avatar-input]')?.addEventListener('change',event=>{const file=event.target.files[0];if(!file)return;const preview=document.querySelector('#avatar-preview');preview.src=URL.createObjectURL(file);preview.hidden=false;document.querySelector('#avatar-initials')?.remove()});
document.querySelectorAll('.main-nav a').forEach(link=>{if(link.textContent.trim()==='Mon profil'){const base=location.pathname.split('/academie')[0].split('/cours')[0].split('/catalogue')[0];link.href=`${base}/profil`;}});

const cloneRepeat=(listSelector)=>{const list=document.querySelector(listSelector);if(!list)return;const field=list.querySelector('.repeat-field').cloneNode(true);field.querySelectorAll('input').forEach(input=>input.value='');field.querySelectorAll('select').forEach(select=>select.value='');list.appendChild(field)};
document.querySelector('[data-add-skill]')?.addEventListener('click',()=>cloneRepeat('[data-skill-list]'));
document.querySelector('[data-add-course]')?.addEventListener('click',()=>cloneRepeat('[data-course-list]'));
document.addEventListener('click',event=>{if(!event.target.matches('[data-remove-field]'))return;const field=event.target.closest('.repeat-field'),list=field.parentElement;if(list.querySelectorAll('.repeat-field').length>1)field.remove();else field.querySelectorAll('input,select').forEach(input=>input.value='')});

document.querySelectorAll('form.builder-form').forEach(form=>{
  const action=form.querySelector('[name=builder_action]')?.value;
  if(action!=='lesson')return;
  const textarea=form.querySelector('[name=content]'),type=form.querySelector('[name=type]');if(!textarea||!type)return;
  const editor=document.createElement('div');editor.className='rich-editor full';editor.innerHTML='<div class="rich-toolbar"><select data-format><option value="p">Paragraphe</option><option value="h2">Titre 1</option><option value="h3">Titre 2</option><option value="blockquote">Citation</option></select><button type="button" data-cmd="bold"><b>G</b></button><button type="button" data-cmd="italic"><i>I</i></button><button type="button" data-cmd="underline"><u>S</u></button><button type="button" data-cmd="insertUnorderedList">• Liste</button><button type="button" data-cmd="insertOrderedList">1. Liste</button><button type="button" data-link>🔗 Lien</button><button type="button" data-cmd="removeFormat">Effacer</button></div><div class="rich-canvas" contenteditable="true" data-placeholder="Rédigez la leçon ici..."></div><small>Mise en forme enregistrée automatiquement et sécurisée côté serveur.</small>';
  textarea.insertAdjacentElement('afterend',editor);const canvas=editor.querySelector('.rich-canvas');
  editor.querySelectorAll('[data-cmd]').forEach(button=>button.addEventListener('click',()=>{document.execCommand(button.dataset.cmd,false);canvas.focus()}));
  editor.querySelector('[data-format]').addEventListener('change',event=>{document.execCommand('formatBlock',false,event.target.value);canvas.focus()});
  editor.querySelector('[data-link]').addEventListener('click',()=>{const url=prompt('Adresse du lien (https://...)');if(url)document.execCommand('createLink',false,url)});
  const sync=()=>textarea.value=canvas.innerHTML;canvas.addEventListener('input',sync);form.addEventListener('submit',sync);
  const toggle=()=>{const textMode=type.value==='text';editor.hidden=!textMode;textarea.hidden=textMode;if(textMode&&textarea.value&&!canvas.innerHTML)canvas.innerHTML=textarea.value;};type.addEventListener('change',toggle);toggle();
});
