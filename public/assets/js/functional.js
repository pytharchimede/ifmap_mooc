document.querySelectorAll("[data-table-search]").forEach((input) =>
  input.addEventListener("input", () => {
    const query = input.value.toLocaleLowerCase();
    document
      .querySelectorAll("[data-search-row]")
      .forEach(
        (row) =>
          (row.hidden = !row.textContent.toLocaleLowerCase().includes(query)),
      );
  }),
);

document.querySelectorAll("[data-account-dropdown]").forEach((dropdown) => {
  const trigger = dropdown.querySelector("[data-account-toggle]");
  const menu = dropdown.querySelector("[data-account-menu]");
  const close = () => { dropdown.classList.remove("open"); menu.hidden = true; trigger.setAttribute("aria-expanded", "false"); };
  trigger.addEventListener("click", (event) => { event.stopPropagation(); const opening = menu.hidden; document.querySelectorAll("[data-account-dropdown].open").forEach((item) => { if (item !== dropdown) item.querySelector("[data-account-toggle]").click(); }); menu.hidden = !opening; dropdown.classList.toggle("open", opening); trigger.setAttribute("aria-expanded", String(opening)); });
  dropdown.addEventListener("click", (event) => event.stopPropagation());
  document.addEventListener("click", close);
  document.addEventListener("keydown", (event) => { if (event.key === "Escape") close(); });
});

const certificateTools = document.querySelector(".document-tools");
if (certificateTools && location.pathname.endsWith("/academie/diplome")) {
  const id = new URLSearchParams(location.search).get("id");
  if (id) {
    const link = document.createElement("a");
    link.href = `${location.pathname.replace("/diplome", "/attestation")}?id=${id}`;
    link.textContent = "Attestation de fin de formation";
    certificateTools.append(link);
  }
}
document.querySelectorAll(".my-course-card").forEach((card) => {
  const courseLink = card.querySelector('a[href*="/academie/formation?course="]');
  const actions = card.querySelector("footer > div");
  if (!courseLink || !actions) return;
  const id = new URL(courseLink.href).searchParams.get("course");
  const transcript = document.createElement("a");
  transcript.className = "btn secondary";
  transcript.href = `${location.pathname.split("/cours")[0]}/academie/livret?course=${id}`;
  transcript.textContent = "Livret scolaire";
  actions.prepend(transcript);
});
const ordersExport = document.querySelector('a[href$="/admin/documents/export-commandes"]');
if (ordersExport && location.pathname.endsWith("/admin/documents")) {
  const enrollmentsExport = document.createElement("a");
  enrollmentsExport.className = "btn secondary";
  enrollmentsExport.href = ordersExport.href.replace("export-commandes", "export-inscriptions");
  enrollmentsExport.textContent = "Inscriptions Excel/CSV";
  ordersExport.after(enrollmentsExport);
}

const trainingCatalog = document.querySelector("[data-training-catalog]");
if (trainingCatalog) {
  const normalize = (value) => value.toLocaleLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  const cards = [...trainingCatalog.querySelectorAll(".training-card")];
  const grid = trainingCatalog.querySelector("[data-training-grid]");
  const search = document.querySelector("[data-training-search]");
  const sort = trainingCatalog.querySelector("[data-training-sort]");
  const price = trainingCatalog.querySelector("[data-max-price]");
  const applyFilters = () => {
    const query = normalize(search.value.trim());
    const sectors = [...trainingCatalog.querySelectorAll('[name="sector"]:checked')].map((item) => item.value);
    const modes = [...trainingCatalog.querySelectorAll('[name="mode"]:checked')].map((item) => item.value);
    const maximum = Number(price.value) || Infinity;
    let visible = 0;
    cards.forEach((card) => {
      const show = (!query || normalize(card.dataset.title).includes(query)) && (!sectors.length || sectors.includes(card.dataset.sector)) && (!modes.length || modes.includes(card.dataset.mode)) && Number(card.dataset.price) <= maximum;
      card.hidden = !show;
      if (show) visible++;
    });
    trainingCatalog.querySelector("[data-result-count]").textContent = visible;
    trainingCatalog.querySelector("[data-catalog-empty]").hidden = visible !== 0;
    const chips = trainingCatalog.querySelector("[data-active-filters]");
    const values = [...sectors, ...modes, ...(price.value ? [`≤ ${Number(price.value).toLocaleString("fr-FR")} FCFA`] : [])];
    chips.hidden = values.length === 0;
    chips.innerHTML = values.map((value) => `<span>${value}</span>`).join("");
  };
  const applySort = () => {
    const mode = sort.value;
    [...cards].sort((a, b) => mode === "price-asc" ? a.dataset.price - b.dataset.price : mode === "price-desc" ? b.dataset.price - a.dataset.price : mode === "title" ? a.dataset.title.localeCompare(b.dataset.title) : 0).forEach((card) => grid.append(card));
  };
  search.addEventListener("input", applyFilters);
  price.addEventListener("input", applyFilters);
  trainingCatalog.querySelectorAll('input[type="checkbox"]').forEach((input) => input.addEventListener("change", applyFilters));
  sort.addEventListener("change", applySort);
  document.querySelectorAll("[data-reset-filters]").forEach((button) => button.addEventListener("click", () => { search.value = ""; price.value = ""; trainingCatalog.querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = false); applyFilters(); }));
  trainingCatalog.querySelector("[data-toggle-filters]")?.addEventListener("click", () => trainingCatalog.querySelector("[data-filter-panel]").classList.toggle("open"));
  document.addEventListener("keydown", (event) => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") { event.preventDefault(); search.focus(); } });
}

document
  .querySelectorAll(".program-line")
  .forEach((line) =>
    line.addEventListener("click", () => line.classList.toggle("expanded")),
  );

document
  .querySelector(".lesson-nav .primary")
  ?.addEventListener("click", (event) => {
    event.currentTarget.textContent = "✓ Leçon terminée";
    event.currentTarget.disabled = true;
    const progress = document.querySelector(".learn-progress .progress span");
    if (progress) progress.style.width = "76%";
  });

document
  .querySelectorAll('form[action$="/admin/cours/supprimer"]')
  .forEach((form) => {
    const id = form.querySelector("[name=id]")?.value;
    if (!id) return;
    const base = location.pathname.split("/admin/")[0];
    const program = document.createElement("a");
    program.className = "btn secondary";
    program.textContent = "Programme";
    program.href = `${base}/admin/cours/programme?course=${id}`;
    const exams = document.createElement("a");
    exams.className = "btn secondary";
    exams.textContent = "Examens";
    exams.href = `${base}/admin/cours/examens?course=${id}`;
    const preview = document.createElement("a");
    preview.className = "btn secondary";
    preview.textContent = "Voir le cours";
    preview.href = `${base}/academie/formation?course=${id}`;
    preview.target = "_blank";
    const edit = document.createElement("a");
    edit.className = "btn secondary";
    edit.textContent = "Modifier";
    edit.href = `${base}/admin/cours/modifier?id=${id}`;
    form.parentElement.prepend(program);
    form.parentElement.prepend(exams);
    form.parentElement.prepend(preview);
    form.parentElement.prepend(edit);
  });

const builderCourseId = document.querySelector(
  '.builder-layout input[name="course_id"]',
)?.value;
if (builderCourseId) {
  const base = location.pathname.split("/admin/")[0];
  const actions = document.querySelector(".admin-welcome > a")?.parentElement;
  if (actions) {
    const exams = document.createElement("a");
    exams.className = "btn secondary";
    exams.textContent = "Voir les examens";
    exams.href = `${base}/admin/cours/examens?course=${builderCourseId}`;
    const preview = document.createElement("a");
    preview.className = "btn primary";
    preview.textContent = "Prévisualiser le cours";
    preview.href = `${base}/academie/formation?course=${builderCourseId}`;
    preview.target = "_blank";
    actions.append(exams, preview);
  }
}

document.querySelectorAll("form.builder-form").forEach((form) => {
  const action = form.querySelector("[name=builder_action]")?.value;
  if (action === "lesson") {
    form.enctype = "multipart/form-data";
    const type = form.querySelector("[name=type]");
    if (type) {
      type.setAttribute("aria-label", "Type de contenu");
      type.title = "Choisissez Vidéo, Article ou Ressource";
    }
    const duration = form.querySelector("[name=duration]");
    if (duration) {
      duration.placeholder = "Durée en minutes";
      duration.title = "Durée estimée de la leçon en minutes";
      const label = document.createElement("label");
      label.textContent = "Durée (minutes)";
      label.style.cssText =
        "display:flex;flex-direction:column;gap:5px;font-size:9px;font-weight:700";
      duration.parentNode.insertBefore(label, duration);
      label.appendChild(duration);
    }
    const content = form.querySelector("[name=content]");
    if (content)
      content.placeholder = "Texte de la leçon, URL vidéo ou URL du document";
    if (content) {
      const upload = document.createElement("div");
      upload.className = "lesson-upload full";
      upload.innerHTML =
        '<label class="upload-label">Ou sélectionner un fichier<input type="file" name="attachment" accept="video/mp4,video/webm,application/pdf,.docx,.pptx,.zip"></label><small>MP4, WebM, PDF, DOCX, PPTX ou ZIP · 150 Mo maximum</small><div class="lesson-live-preview"><em>L’aperçu apparaîtra ici.</em></div>';
      content.insertAdjacentElement("afterend", upload);
      const file = upload.querySelector("input[type=file]"),
        preview = upload.querySelector(".lesson-live-preview");
      const renderUrl = (url) => {
        const kind = type?.value;
        if (kind === "video")
          preview.innerHTML = `<video src="${url}" controls></video>`;
        else if (kind === "file" && url.toLowerCase().includes(".pdf"))
          preview.innerHTML = `<iframe src="${url}"></iframe>`;
        else
          preview.innerHTML = url
            ? `<div class="resource-preview">${url}</div>`
            : "<em>L’aperçu apparaîtra ici.</em>";
      };
      file.addEventListener("change", () => {
        if (file.files[0]) renderUrl(URL.createObjectURL(file.files[0]));
      });
      content.addEventListener("input", () => renderUrl(content.value.trim()));
      type?.addEventListener("change", () => {
        if (file.files[0]) renderUrl(URL.createObjectURL(file.files[0]));
        else renderUrl(content.value.trim());
      });
    }
  }
  if (action === "assessment") {
    const score = form.querySelector("[name=passing_score]");
    if (score) score.title = "Pourcentage minimum nécessaire pour réussir";
    const attempts = form.querySelector("[name=attempts_allowed]");
    if (attempts) attempts.title = "Nombre maximum de compositions autorisées";
  }
});

document.querySelectorAll("[data-password]").forEach((button) =>
  button.addEventListener("click", () => {
    const input = button.previousElementSibling;
    input.type = input.type === "password" ? "text" : "password";
    button.textContent = input.type === "password" ? "Afficher" : "Masquer";
  }),
);
const updateProfileFile = (input, file, type) => {
  if (!file) return;
  const transfer = new DataTransfer();
  transfer.items.add(file);
  input.files = transfer.files;
  const label = document.querySelector(`[data-file-name="${type}"]`);
  if (label) label.textContent = file.name;
  if (type === "avatar") {
    const preview = document.querySelector("#avatar-preview");
    preview.src = URL.createObjectURL(file);
    preview.hidden = false;
    document.querySelector("#avatar-initials")?.remove();
  }
};
document.querySelectorAll("[data-dropzone]").forEach((zone) => {
  const type = zone.dataset.dropzone;
  const input = zone.querySelector(type === "avatar" ? "[data-avatar-input]" : "[data-cv-input]");
  if (!input) return;
  zone.addEventListener("click", () => input.click());
  zone.addEventListener("keydown", (event) => { if (event.key === "Enter" || event.key === " ") { event.preventDefault(); input.click(); } });
  ["dragenter", "dragover"].forEach((name) => zone.addEventListener(name, (event) => { event.preventDefault(); zone.classList.add("is-dragging"); }));
  ["dragleave", "drop"].forEach((name) => zone.addEventListener(name, (event) => { event.preventDefault(); zone.classList.remove("is-dragging"); }));
  zone.addEventListener("drop", (event) => updateProfileFile(input, event.dataTransfer.files[0], type));
  input.addEventListener("change", () => updateProfileFile(input, input.files[0], type));
});
document.querySelectorAll(".main-nav a").forEach((link) => {
  if (link.textContent.trim() === "Mon profil") {
    const base = location.pathname
      .split("/academie")[0]
      .split("/cours")[0]
      .split("/catalogue")[0];
    link.href = `${base}/profil`;
  }
});

const cloneRepeat = (listSelector) => {
  const list = document.querySelector(listSelector);
  if (!list) return;
  const field = list.querySelector(".repeat-field").cloneNode(true);
  const enhanced = field.querySelector(".search-select");
  if (enhanced) enhanced.replaceWith(enhanced.querySelector("select"));
  field.querySelectorAll("input").forEach((input) => (input.value = ""));
  field.querySelectorAll("select").forEach((select) => {
    select.value = "";
    delete select.dataset.searchReady;
  });
  list.appendChild(field);
  field.querySelectorAll("select").forEach(setupSearchSelect);
};
document
  .querySelector("[data-add-skill]")
  ?.addEventListener("click", () => cloneRepeat("[data-skill-list]"));
document
  .querySelector("[data-add-course]")
  ?.addEventListener("click", () => cloneRepeat("[data-course-list]"));
document.addEventListener("click", (event) => {
  if (!event.target.matches("[data-remove-field]")) return;
  const field = event.target.closest(".repeat-field"),
    list = field.parentElement;
  if (list.querySelectorAll(".repeat-field").length > 1) field.remove();
  else
    field
      .querySelectorAll("input,select")
      .forEach((input) => (input.value = ""));
});

const setupSearchSelect = (select) => {
  if (select.dataset.searchReady) return;
  select.dataset.searchReady = "true";
  const wrapper = document.createElement("div");
  wrapper.className = "search-select";
  const search = document.createElement("input");
  search.type = "search";
  search.className = "search-select-input";
  search.placeholder = select.querySelector('option[value=""]')
    ? "Aucune formation obligatoire"
    : "Rechercher...";
  search.autocomplete = "off";
  search.setAttribute("aria-label", "Rechercher une option");
  const options = document.createElement("div");
  options.className = "search-select-options";
  options.setAttribute("role", "listbox");
  options.hidden = true;
  select.hidden = true;
  select.parentNode.insertBefore(wrapper, select);
  wrapper.append(search, options, select);

  const renderOptions = () => {
    const query = search.value.toLocaleLowerCase();
    options.innerHTML = "";
    [...select.options]
      .filter((option) => option.value !== "")
      .filter((option) =>
        option.textContent.toLocaleLowerCase().includes(query),
      )
      .forEach((option) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "search-select-option";
        item.textContent = option.textContent;
        item.dataset.value = option.value;
        item.setAttribute("role", "option");
        item.setAttribute("aria-selected", option.selected ? "true" : "false");
        item.addEventListener("click", () => {
          select.value = option.value;
          search.value = option.textContent.trim();
          options.hidden = true;
          select.dispatchEvent(new Event("change", { bubbles: true }));
        });
        options.appendChild(item);
      });
  };

  const sync = () => {
    search.value = select.value
      ? select.selectedOptions[0]?.textContent.trim() || ""
      : "";
    renderOptions();
  };
  search.addEventListener("focus", () => {
    if (!select.value) search.value = "";
    renderOptions();
    options.hidden = false;
  });
  search.addEventListener("input", () => {
    renderOptions();
    options.hidden = false;
  });
  document.addEventListener("click", (event) => {
    if (!wrapper.contains(event.target)) options.hidden = true;
  });
  select.addEventListener("change", sync);
  sync();
};

const setupThumbnailDropzone = (input) => {
  if (input.dataset.dropzoneReady) return;
  input.dataset.dropzoneReady = "true";
  const label = input.closest("label");
  if (!label) return;
  const zone = document.createElement("div");
  zone.className = "thumbnail-dropzone";
  zone.tabIndex = 0;
  zone.innerHTML =
    '<span class="dropzone-icon">↑</span><strong>Déposez votre image ici</strong><small>ou cliquez pour parcourir · JPG, PNG ou WebP · 5 Mo maximum</small><div class="thumbnail-live-preview"></div><button type="button" class="dropzone-reset" hidden>Supprimer</button>';
  const preview = zone.querySelector(".thumbnail-live-preview");
  const reset = zone.querySelector(".dropzone-reset");
  const existing = label.querySelector(".thumbnail-preview");
  if (existing) {
    const image = existing.cloneNode(true);
    image.className = "thumbnail-preview";
    preview.appendChild(image);
    existing.remove();
    reset.hidden = false;
  }
  input.hidden = true;
  label.insertBefore(zone, input);

  const showFile = (file) => {
    if (!file) return;
    if (
      !["image/jpeg", "image/png", "image/webp"].includes(file.type) ||
      file.size > 5 * 1024 * 1024
    ) {
      input.value = "";
      zone.classList.add("is-invalid");
      zone.querySelector("small").textContent =
        "Image invalide : JPG, PNG ou WebP, 5 Mo maximum.";
      return;
    }
    zone.classList.remove("is-invalid");
    zone.querySelector("small").textContent = file.name;
    preview.innerHTML =
      '<img class="thumbnail-preview" alt="Aperçu de la vignette">';
    preview.querySelector("img").src = URL.createObjectURL(file);
    reset.hidden = false;
  };
  zone.addEventListener("click", (event) => {
    if (event.target !== reset) input.click();
  });
  zone.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      input.click();
    }
  });
  ["dragenter", "dragover"].forEach((type) =>
    zone.addEventListener(type, (event) => {
      event.preventDefault();
      zone.classList.add("is-dragging");
    }),
  );
  ["dragleave", "drop"].forEach((type) =>
    zone.addEventListener(type, (event) => {
      event.preventDefault();
      zone.classList.remove("is-dragging");
    }),
  );
  zone.addEventListener("drop", (event) => {
    const file = event.dataTransfer.files[0];
    if (file) {
      input.files = event.dataTransfer.files;
      showFile(file);
    }
  });
  input.addEventListener("change", () => showFile(input.files[0]));
  reset.addEventListener("click", (event) => {
    event.stopPropagation();
    input.value = "";
    preview.innerHTML = "";
    reset.hidden = true;
    zone.querySelector("small").textContent =
      "ou cliquez pour parcourir · JPG, PNG ou WebP · 5 Mo maximum";
  });
};

document.querySelectorAll(".admin-editor select").forEach(setupSearchSelect);
document
  .querySelectorAll('.admin-editor input[type="file"][name="thumbnail"]')
  .forEach(setupThumbnailDropzone);

document.querySelectorAll("form.builder-form").forEach((form) => {
  const action = form.querySelector("[name=builder_action]")?.value;
  if (action !== "lesson") return;
  const textarea = form.querySelector("[name=content]"),
    type = form.querySelector("[name=type]");
  if (!textarea || !type) return;
  const editor = document.createElement("div");
  editor.className = "rich-editor full";
  editor.innerHTML =
    '<div class="rich-toolbar"><select data-format><option value="p">Paragraphe</option><option value="h2">Titre 1</option><option value="h3">Titre 2</option><option value="blockquote">Citation</option></select><button type="button" data-cmd="bold"><b>G</b></button><button type="button" data-cmd="italic"><i>I</i></button><button type="button" data-cmd="underline"><u>S</u></button><button type="button" data-cmd="insertUnorderedList">• Liste</button><button type="button" data-cmd="insertOrderedList">1. Liste</button><button type="button" data-link>🔗 Lien</button><button type="button" data-cmd="removeFormat">Effacer</button></div><div class="rich-canvas" contenteditable="true" data-placeholder="Rédigez la leçon ici..."></div><small>Mise en forme enregistrée automatiquement et sécurisée côté serveur.</small>';
  textarea.insertAdjacentElement("afterend", editor);
  const canvas = editor.querySelector(".rich-canvas");
  editor.querySelectorAll("[data-cmd]").forEach((button) =>
    button.addEventListener("click", () => {
      document.execCommand(button.dataset.cmd, false);
      canvas.focus();
    }),
  );
  editor.querySelector("[data-format]").addEventListener("change", (event) => {
    document.execCommand("formatBlock", false, event.target.value);
    canvas.focus();
  });
  editor.querySelector("[data-link]").addEventListener("click", () => {
    const url = prompt("Adresse du lien (https://...)");
    if (url) document.execCommand("createLink", false, url);
  });
  const sync = () => (textarea.value = canvas.innerHTML);
  canvas.addEventListener("input", sync);
  form.addEventListener("submit", sync);
  const toggle = () => {
    const textMode = type.value === "text";
    editor.hidden = !textMode;
    textarea.hidden = textMode;
    if (textMode && textarea.value && !canvas.innerHTML)
      canvas.innerHTML = textarea.value;
  };
  type.addEventListener("change", toggle);
  toggle();
});

const detailTabs = document.querySelectorAll("[data-detail-tab]");
if (detailTabs.length) {
  const detailPanels = document.querySelectorAll(".course-tab-panel");
  const activateDetailTab = (tabName) => {
    detailTabs.forEach((tab) => {
      const isActive = tab.dataset.detailTab === tabName;
      tab.classList.toggle("active", isActive);
      tab.setAttribute("aria-selected", isActive ? "true" : "false");
    });
    detailPanels.forEach((panel) => {
      panel.hidden = panel.id !== tabName;
    });
  };

  detailTabs.forEach((tab) => {
    tab.addEventListener("click", () =>
      activateDetailTab(tab.dataset.detailTab),
    );
  });
  activateDetailTab("programme");
}

document.querySelectorAll("[data-copy-url]").forEach((button) => {
  button.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(button.dataset.copyUrl);
      const label = button.textContent;
      button.textContent = "Lien copié ✓";
      setTimeout(() => (button.textContent = label), 1800);
    } catch (_) {
      window.prompt("Copiez ce lien", button.dataset.copyUrl);
    }
  });
});

document.querySelectorAll("[data-news-content]").forEach((textarea) => {
  const editor = document.createElement("div");
  editor.className = "rich-editor full";
  editor.innerHTML = '<div class="rich-toolbar"><select data-format><option value="p">Paragraphe</option><option value="h2">Grand titre</option><option value="h3">Sous-titre</option><option value="blockquote">Citation</option></select><button type="button" data-cmd="bold"><b>G</b></button><button type="button" data-cmd="italic"><i>I</i></button><button type="button" data-cmd="underline"><u>S</u></button><button type="button" data-cmd="insertUnorderedList">• Liste</button><button type="button" data-cmd="insertOrderedList">1. Liste</button><button type="button" data-link>🔗 Lien</button><button type="button" data-cmd="removeFormat">Effacer</button></div><div class="rich-canvas" contenteditable="true" data-placeholder="Rédigez un article complet, structurez les titres et ajoutez des liens..."></div><small>Le contenu est nettoyé et sécurisé lors de la publication.</small>';
  textarea.hidden = true;
  textarea.insertAdjacentElement("afterend", editor);
  const canvas = editor.querySelector(".rich-canvas");
  canvas.innerHTML = textarea.value;
  editor.querySelectorAll("[data-cmd]").forEach((button) =>
    button.addEventListener("click", () => {
      document.execCommand(button.dataset.cmd, false);
      canvas.focus();
    }),
  );
  editor.querySelector("[data-format]").addEventListener("change", (event) => {
    document.execCommand("formatBlock", false, event.target.value);
    canvas.focus();
  });
  editor.querySelector("[data-link]").addEventListener("click", () => {
    const url = prompt("Adresse du lien (https://...)");
    if (url) document.execCommand("createLink", false, url);
  });
  const sync = () => (textarea.value = canvas.innerHTML);
  canvas.addEventListener("input", sync);
  textarea.form?.addEventListener("submit", sync);
});

document.querySelectorAll("[data-news-form]").forEach((form) => {
  const file = form.querySelector("[data-news-cover]");
  const url = form.querySelector('[name="cover_url"]');
  const preview = form.querySelector("[data-news-image-preview]");
  const show = (source) => {
    if (!source || !preview) return;
    preview.style.backgroundImage = `url("${source.replace(/"/g, "")}")`;
    preview.hidden = false;
  };
  file?.addEventListener("change", () => {
    if (file.files[0]) show(URL.createObjectURL(file.files[0]));
  });
  url?.addEventListener("input", () => show(url.value));
});
