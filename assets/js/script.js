/* script.js — fusion de main.js (Orbit) + uwb-data.js + uwb-app.js + uwb-ia.js */

/* UWB — helper chemins (racine vs sous-dossiers admin/etudiant/prof) */
function uwbBase(){ try{ if(/\/(admin|etudiant|prof)\//.test(location.pathname)) return '../'; }catch(e){} return ''; }
function uwbPath(p){ return uwbBase()+p; }

/* ===== main.js ===== */
/**
* Template Name: Orbit
* Template URL: https://bootstrapmade.com/orbit-bootstrap-template/
* Updated: Jan 13 2026 with Bootstrap v5.3.8
* Author: BootstrapMade.com
* License: https://bootstrapmade.com/license/
*/

(function() {
  "use strict";

  /**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header'); if(!selectHeader) return;
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

  function mobileNavToogle() {
    document.querySelector('body').classList.toggle('mobile-nav-active');
    mobileNavToggleBtn.classList.toggle('bi-list');
    mobileNavToggleBtn.classList.toggle('bi-x');
  }
  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.addEventListener('click', mobileNavToogle);
  }

  /**
   * Hide mobile nav on same-page/hash links
   */
  document.querySelectorAll('#navmenu a').forEach(navmenu => {
    navmenu.addEventListener('click', () => {
      if (document.querySelector('.mobile-nav-active')) {
        mobileNavToogle();
      }
    });

  });

  /**
   * Toggle mobile nav dropdowns
   */
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(navmenu => {
    navmenu.addEventListener('click', function(e) {
      e.preventDefault();
      this.parentNode.classList.toggle('active');
      this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
      e.stopImmediatePropagation();
    });
  });

  /**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove();
    });
  }

  /**
   * Scroll top button
   */
  let scrollTop = document.querySelector('.scroll-top');

  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  if (scrollTop) { scrollTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  }); }

  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);

  /**
   * Animation on scroll function and init
   */
  function aosInit() {
    if (typeof AOS === 'undefined') return;
    AOS.init({
      duration: 600,
      easing: 'ease-in-out',
      once: true,
      mirror: false
    });
  }
  window.addEventListener('load', aosInit);

  /**
   * Initiate glightbox
   */
  const glightbox = (typeof GLightbox !== 'undefined') ? GLightbox({
    selector: '.glightbox'
  }) : null;

  /**
   * Init isotope layout and filters
   */
  document.querySelectorAll('.isotope-layout').forEach(function(isotopeItem) {
    let layout = isotopeItem.getAttribute('data-layout') ?? 'masonry';
    let filter = isotopeItem.getAttribute('data-default-filter') ?? '*';
    let sort = isotopeItem.getAttribute('data-sort') ?? 'original-order';

    let initIsotope;
    imagesLoaded(isotopeItem.querySelector('.isotope-container'), function() {
      initIsotope = new Isotope(isotopeItem.querySelector('.isotope-container'), {
        itemSelector: '.isotope-item',
        layoutMode: layout,
        filter: filter,
        sortBy: sort
      });
    });

    isotopeItem.querySelectorAll('.isotope-filters li').forEach(function(filters) {
      filters.addEventListener('click', function() {
        isotopeItem.querySelector('.isotope-filters .filter-active').classList.remove('filter-active');
        this.classList.add('filter-active');
        initIsotope.arrange({
          filter: this.getAttribute('data-filter')
        });
        if (typeof aosInit === 'function') {
          aosInit();
        }
      }, false);
    });

  });

  /**
   * Initiate Pure Counter
   */
  if (typeof PureCounter !== 'undefined') { new PureCounter(); }

  /**
   * Init swiper sliders
   */
  function initSwiper() {
    document.querySelectorAll(".init-swiper").forEach(function(swiperElement) {
      let config = JSON.parse(
        swiperElement.querySelector(".swiper-config").innerHTML.trim()
      );

      if (swiperElement.classList.contains("swiper-tab")) {
        initSwiperWithCustomPagination(swiperElement, config);
      } else if (typeof Swiper !== 'undefined') {
        new Swiper(swiperElement, config);
      }
    });
  }

  window.addEventListener("load", initSwiper);

  /**
   * Correct scrolling position upon page load for URLs containing hash links.
   */
  window.addEventListener('load', function(e) {
    if (window.location.hash) {
      if (document.querySelector(window.location.hash)) {
        setTimeout(() => {
          let section = document.querySelector(window.location.hash);
          let scrollMarginTop = getComputedStyle(section).scrollMarginTop;
          window.scrollTo({
            top: section.offsetTop - parseInt(scrollMarginTop),
            behavior: 'smooth'
          });
        }, 100);
      }
    }
  });

  /**
   * Navmenu Scrollspy
   */
  let navmenulinks = document.querySelectorAll('.navmenu a');

  function navmenuScrollspy() {
    navmenulinks.forEach(navmenulink => {
      if (!navmenulink.hash) return;
      let section = document.querySelector(navmenulink.hash);
      if (!section) return;
      let position = window.scrollY + 200;
      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        document.querySelectorAll('.navmenu a.active').forEach(link => link.classList.remove('active'));
        navmenulink.classList.add('active');
      } else {
        navmenulink.classList.remove('active');
      }
    })
  }
  window.addEventListener('load', navmenuScrollspy);
  document.addEventListener('scroll', navmenuScrollspy);

})();

/* ===== uwb-data.js ===== */
/* UWB — Base de données de démonstration (localStorage) */
const UWB_SEED = {
  users: [
    {id:'u-admin', nom:'Admin UWB', email:'admin@uwb.ac.cd', pass:'admin123', role:'admin', filiere:'Administration'},
    {id:'u-prof1', nom:'Prof. Mbuyi Kalonji', email:'prof@uwb.ac.cd', pass:'prof123', role:'professeur', filiere:'Informatique'},
    {id:'u-prof2', nom:'Prof. Aline Nsimba', email:'aline@uwb.ac.cd', pass:'prof123', role:'professeur', filiere:'Gestion'},
    {id:'u-etu1', nom:'Grace Lukusa', email:'etudiant@uwb.ac.cd', pass:'etu123', role:'etudiant', filiere:'L3 Informatique'},
    {id:'u-etu2', nom:'Jean Ilunga', email:'jean@uwb.ac.cd', pass:'etu123', role:'etudiant', filiere:'L2 Gestion'}
  ],
  cours: [
    {id:'c1', titre:'Programmation Web : HTML, CSS & JavaScript', enseignant:'Prof. Mbuyi Kalonji', categorie:'Informatique', niveau:'L3', description:"Création d'interfaces modernes, Bootstrap 5, DOM, fetch API et bonnes pratiques.", supports:['Support PDF - HTML5.pdf','TP N°3 - Portfolio Bootstrap.zip'], seance:{date:'2026-10-07 10:00', meet:'https://meet.google.com/uwb-web-101', salle:'Visioconférence Google Meet'}, inscrits:42, image:'forms/portfolio-8.webp'},
    {id:'c2', titre:'Bases de données & SQL', enseignant:'Prof. Mbuyi Kalonji', categorie:'Informatique', niveau:'L2', description:'Modèle relationnel, MCD/MLD, requêtes SQL, vues et transactions.', supports:['MCD - Gestion UWB.pdf','TD SQL corrigé.pdf'], seance:{date:'2026-10-08 14:00', meet:'https://meet.google.com/uwb-sql-202', salle:'Visioconférence Google Meet'}, inscrits:38, image:'forms/portfolio-2.webp'},
    {id:'c3', titre:'Comptabilité Générale OHADA', enseignant:'Prof. Aline Nsimba', categorie:'Gestion', niveau:'L2', description:'Plan comptable OHADA, journal, grand-livre, balance et états financiers.', supports:['Plan OHADA résumé.pdf'], seance:{date:'2026-10-09 09:00', meet:'https://meet.google.com/uwb-ohada-303', salle:'Visioconférence Google Meet'}, inscrits:55, image:'forms/portfolio-6.webp'},
    {id:'c4', titre:'Intelligence Artificielle : introduction au NLP', enseignant:'Prof. Mbuyi Kalonji', categorie:'Informatique', niveau:'M1', description:'Traitement du langage, embeddings, correction automatique et feedback pédagogique.', supports:['Intro NLP - slides.pdf'], seance:{date:'2026-10-10 11:00', meet:'https://meet.google.com/uwb-ia-404', salle:'Visioconférence Google Meet'}, inscrits:27, image:'forms/portfolio-7.webp'}
  ],
  livres: [
    {id:'b1', titre:'Apprendre JavaScript Moderne', auteur:'M. Lelo', categorie:'Informatique', annee:2023, mots:['javascript','web','programmation','html','css'], resume:"Bases JS, ES6+, DOM et projets web.", telechargements:312},
    {id:'b2', titre:'Systèmes de Gestion de Bases de Données', auteur:'R. Elmasri', categorie:'Informatique', annee:2022, mots:['sql','mcd','base de données','merise'], resume:'Conception MCD/MLD et SQL avancé.', telechargements:428},
    {id:'b3', titre:'Comptabilité OHADA en pratique', auteur:'A. Nsimba', categorie:'Gestion', annee:2024, mots:['ohada','comptabilité','gestion','finance'], resume:'Écritures, états financiers, cas pratiques RDC.', telechargements:265},
    {id:'b4', titre:'Introduction au Machine Learning', auteur:'A. Ng - adapté UWB', categorie:'Informatique', annee:2024, mots:['ia','machine learning','nlp','correction automatique','python'], resume:'Régression, classification, NLP et évaluation.', telechargements:198},
    {id:'b5', titre:'Méthodologie de recherche (TFE & Mémoire)', auteur:'Dir. Recherche UWB', categorie:'Méthodologie', annee:2023, mots:['tfe','mémoire','problématique','objectifs'], resume:'Problématique, revue de littérature, plan de rédaction.', telechargements:340},
    {id:'b6', titre:'Réseaux informatiques CCNA - Essentiel', auteur:'Cisco Academy', categorie:'Informatique', annee:2022, mots:['réseau','tcp','ip','cisco'], resume:'Adressage IP, routage, switching.', telechargements:150}
  ],
  evaluations: [
    {
      id:'e1', coursId:'c1', titre:'Quiz HTML/CSS - Session 1', prof:'Prof. Mbuyi Kalonji', duree:20, statut:'publiée',
      questions:[
        {id:'q1', type:'qcm', enonce:'Quelle balise crée un lien hypertexte ?', choix:['<link>','<a>','<href>','<url>'], bonne:'<a>', points:5},
        {id:'q2', type:'qcm', enonce:'Quelle classe Bootstrap centre un texte ?', choix:['.text-left','.text-center','.center','.align'], bonne:'.text-center', points:5},
        {id:'q3', type:'ouverte', enonce:'Expliquez en 3 lignes le rôle du JavaScript dans une page web.', motsCles:['interactivité','dynamique','dom','client','navigateur','événement'], points:10}
      ]
    },
    {
      id:'e2', coursId:'c2', titre:'Devoir SQL - Requêtes SELECT', prof:'Prof. Mbuyi Kalonji', duree:45, statut:'publiée',
      questions:[
        {id:'q1', type:'ouverte', enonce:'Écrivez une requête SQL listant les étudiants inscrits en L3 triés par nom.', motsCles:['select','from','where','order by','étudiants'], points:10},
        {id:'q2', type:'qcm', enonce:'Quelle clause filtre les groupes ?', choix:['WHERE','HAVING','GROUP','FILTER'], bonne:'HAVING', points:5},
        {id:'q3', type:'ouverte', enonce:"Citez 2 avantages d'une clé primaire.", motsCles:['unicité','identifiant','intégrité','index','référence'], points:5}
      ]
    }
  ],
  soumissions: [
    {id:'s1', evalId:'e1', etudiant:'Grace Lukusa', email:'etudiant@uwb.ac.cd', reponses:{q1:'<a>', q2:'.text-center', q3:"JavaScript rend la page interactive côté client, manipule le DOM et réagit aux événements du navigateur."}, noteIA:17, noteFinale:17, feedbackIA:"Bonne maîtrise. Q1-Q2 justes. Q3 : mots-clés interactivité, client, DOM, événement détectés.", statut:'validée', date:'2026-09-28'}
  ],
  filieres: [
    {id:'f1', nom:'Informatique', code:'INFO', description:'Programmation, bases de données, réseaux, IA.'},
    {id:'f2', nom:'Gestion', code:'GEST', description:'Comptabilité OHADA, finance, management.'},
    {id:'f3', nom:'Méthodologie', code:'METH', description:'TFE, mémoire, recherche scientifique.'}
  ]
};

const UWB_DB_KEY='uwb_db_v1';
function uwbDefaultFilieres(){ return JSON.parse(JSON.stringify(UWB_SEED.filieres)); }
function uwbLoad(){ try{const r=localStorage.getItem(UWB_DB_KEY); if(r){const d=JSON.parse(r); if(!d.filieres)d.filieres=uwbDefaultFilieres(); return d;}}catch(e){} return JSON.parse(JSON.stringify(UWB_SEED)); }
function uwbSave(db){ localStorage.setItem(UWB_DB_KEY, JSON.stringify(db)); }
function uwbReset(){ localStorage.removeItem(UWB_DB_KEY); return uwbLoad(); }

/* ===== uwb-app.js (chemins reecrits) ===== */
/* UWB — Auth, guards, helpers UI */
const UWB_SESSION='uwb_session_v1';
function uwbSession(){ try{return JSON.parse(localStorage.getItem(UWB_SESSION));}catch(e){return null;} }
function uwbLogin(email,pass){
  const db=uwbLoad();
  const u=db.users.find(x=>x.email.toLowerCase()===email.toLowerCase() && x.pass===pass);
  if(!u) return {error:"Email ou mot de passe incorrect."};
  localStorage.setItem(UWB_SESSION, JSON.stringify({id:u.id,nom:u.nom,email:u.email,role:u.role,filiere:u.filiere}));
  return {user:u};
}
function uwbLogout(){ localStorage.removeItem(UWB_SESSION); location.href=uwbBase()+'login.php'; }
function uwbRegister(nom,email,pass,role,filiere){
  const db=uwbLoad();
  if(db.users.some(x=>x.email.toLowerCase()===email.toLowerCase())) return {error:"Cet email est déjà utilisé. Connectez-vous."};
  if(!['etudiant','professeur'].includes(role)) role='etudiant';
  const u={id:'u'+Date.now(),nom,email,pass,role,filiere:filiere||'—'};
  db.users.push(u); uwbSave(db);
  localStorage.setItem(UWB_SESSION, JSON.stringify({id:u.id,nom:u.nom,email:u.email,role:u.role,filiere:u.filiere}));
  return {user:u};
}
function uwbHomeByRole(role){
  if(role==='admin') return uwbBase()+'admin/dashboard.html';
  if(role==='professeur') return uwbBase()+'prof/dashboard.html';
  return uwbBase()+'etudiant/dashboard.html';
}
function uwbRequire(roles){
  const s=uwbSession();
  if(!s){ location.href=uwbBase()+'login.php'; return null; }
  if(roles && !roles.includes(s.role)){ location.href=uwbHomeByRole(s.role); return null; }
  return s;
}
function uwbUserBadge(s){
  const map={admin:['A','#c026d3'],professeur:['P','#0a2a6b'],etudiant:['E','#059669']};
  const m=map[s.role]||['U','#0a2a6b'];
  return `<span class="avatar-role" style="background:${m[1]}">${m[0]}</span> <strong>${s.nom}</strong><br><small class="text-muted">${s.role} • ${s.filiere}</small>`;
}
function uwbSidebar(active){
  const s=uwbSession(); if(!s) return '';
  const home=uwbHomeByRole(s.role);
  const links=[
    [home,'bi-speedometer2','Mon espace','all'],
    [uwbBase()+'etudiant/cours.html','bi-mortarboard','Cours & Meet','all'],
    [uwbBase()+'etudiant/bibliotheque.html','bi-book','Bibliothèque intelligente','all'],
  ];
  if(s.role!=='admin'){links.push([uwbBase()+'etudiant/evaluations.html','bi-patch-check','Évaluations & IA','all'],[uwbBase()+'etudiant/resultats.html','bi-bar-chart','Résultats','all']);}
  if(s.role==='admin') links.push([uwbBase()+'admin/dashboard.html#users','bi-people','Utilisateurs','admin']);
  let h=`<div class="mb-3">${uwbUserBadge(s)}</div>`;
  links.forEach(([href,ic,label])=>{ const key=href.split('#')[0]; const akey=(active||'').split('#')[0]; h+=`<a href="${href}" class="${akey===key? 'active':''}"><i class="bi ${ic}"></i> ${label}</a>`; });
  h+=`<a href="${uwbBase()}index.html"><i class="bi bi-house"></i> Site vitrine</a><a href="#" onclick="uwbLogout();return false;"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
  <div class="mt-auto p-2 small" style="background:rgba(255,255,255,.12);border-radius:12px">Comptes démo :<br>admin@uwb.ac.cd / admin123<br>prof@uwb.ac.cd / prof123<br>etudiant@uwb.ac.cd / etu123</div>`;
  return h;
}
function uwbToast(msg){ alert(msg); }
/* Navigation restreinte par rôle : prof sans biblio/résultats, admin sans évaluations/résultats */
function uwbNavRole(){
  const s=uwbSession(); if(!s)return;
  document.querySelectorAll('#navmenu a').forEach(a=>{
    const h=a.getAttribute('href');
    if(s.role==='professeur'&&(h==='bibliotheque.html'||h==='resultats.html')){const li=a.closest('li');if(li)li.remove();}
    if(s.role==='admin'&&(h==='evaluations.html'||h==='resultats.html')){const li=a.closest('li');if(li)li.remove();}
  });
}
/* Barre de recherche unifiée : loupe + bouton effacer */
function uwbSearchType(input){const f=input.closest('.search-field,.pro-input');if(f)f.classList.toggle('has-text',(input.value||'').length>0);}
function uwbSearchClear(inputId,fn){const el=document.getElementById(inputId);if(!el)return;el.value='';uwbSearchType(el);el.focus();if(typeof fn==='function')fn();}
function uwbTag(inputId,fn,t){const q=document.getElementById(inputId);if(!q)return;q.value=t;q.dispatchEvent(new Event('input'));q.focus();}
function uwbLivreImg(cat){if(cat==='Gestion')return uwbBase()+'forms/portfolio-6.webp';if(cat==='Méthodologie')return uwbBase()+'forms/services-3.webp';return uwbBase()+'forms/portfolio-2.webp';}

/* Carte cours partagée — module Cours & espace étudiant (rendu identique) */
const UWB_MOIS=['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
function uwbInitials(n){return (n||'?').replace(/^(Prof\.\s*)/,'').trim().split(/\s+/).map(w=>w[0]).slice(0,2).join('').toUpperCase();}
function uwbSupIcon(sp){sp=(sp||'').toLowerCase();if(sp.includes('.pdf'))return 'bi-file-earmark-pdf-fill';if(sp.includes('.zip')||sp.includes('.rar'))return 'bi-file-earmark-zip-fill';return 'bi-file-earmark-text-fill';}
function uwbMeetState(ds){const m=/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(ds||'');if(!m)return null;const dt=new Date(+m[1],+m[2]-1,+m[3],+m[4],+m[5]);const now=new Date();const diff=dt-now;const j=Math.ceil(diff/864e5);let st='future',label='';if(diff<-(36e5)){st='past';label='Terminée';}else if(Math.abs(diff)<=36e5){st='live';label='EN DIRECT';}else if(diff<864e5&&dt.getDate()===now.getDate()){label="Aujourd'hui "+m[4]+'h'+m[5];}else if(j<=1){label='Demain';}else{label='J-'+j;}return {past:st==='past',live:st==='live',st:st,label:label,jour:m[3],mois:UWB_MOIS[+m[2]-1],heure:m[4]+'h'+m[5]};}
function uwbCopyLink(link,btn){const done=()=>{btn.innerHTML='<i class="bi bi-check-lg"></i>';setTimeout(()=>btn.innerHTML='<i class="bi bi-link-45deg"></i>',1500);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(link).then(done).catch(()=>prompt('Copiez le lien :',link));}else{prompt('Copiez le lien :',link);}}
function uwbCoursCard(x,i){const ms=uwbMeetState(x.seance.date);const d=((i||0)%3)*100;
const sup=(x.supports||[]);
return `<div class="col-lg-4 col-md-6 portfolio-item" data-aos="fade-up" data-aos-delay="${d}"><div class="course-min h-100" data-cat="${x.categorie}"><div class="cat-strip"></div><div class="course-min-body"><div class="d-flex gap-1 align-items-center flex-wrap mb-2"><span class="mini-badge">${x.categorie}</span><span class="mini-badge ghost">${x.niveau}</span><span class="ms-auto mini-muted"><i class="bi bi-people"></i> ${x.inscrits} inscrits</span></div><h3>${x.titre}</h3><div class="teacher-mini"><span class="avatar-initials xs">${uwbInitials(x.enseignant)}</span><span>${x.enseignant}</span></div><p class="course-desc">${x.description}</p><div class="supports-mini"><i class="bi bi-paperclip"></i> ${sup.length} support(s)${sup.length?(' — '+sup.slice(0,2).join(' • ')+(sup.length>2?' …':'')):''}</div><div class="meet-strip ${ms&&ms.past?'past':''}"><span class="dot"></span><div class="flex-fill" style="min-width:0"><small class="text-muted">${ms?(ms.past?'Séance terminée':'Prochaine séance'):'Séance à programmer'}</small><br><strong class="small">${ms?(ms.jour+' '+ms.mois+' • '+ms.heure):x.seance.date}</strong></div><a href="${x.seance.meet}" target="_blank" class="btn-join" title="Rejoindre sur Google Meet"><i class="bi bi-camera-video-fill"></i> Rejoindre</a><button class="btn-copy" title="Copier le lien" onclick="uwbCopyLink('${x.seance.meet}',this)"><i class="bi bi-link-45deg"></i></button></div></div></div></div>`;}

/* ===== uwb-ia.js ===== */
/* UWB — Moteur IA simulée : correction QCM + analyse mots-clés + feedback */
function iaNormaliser(s){ return (s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,''); }
function iaCorriger(evaluation, reponses){
  let total=0, max=0; const details=[];
  evaluation.questions.forEach(q=>{
    max+=q.points;
    const rep=(reponses[q.id]||'').toString().trim();
    if(q.type==='qcm'){
      const ok = rep===q.bonne;
      const pts = ok? q.points:0; total+=pts;
      details.push({qid:q.id, type:'qcm', reponse:rep, attendu:q.bonne, points:pts+'/'+q.points, commentaire: ok?'Bonne réponse.':'Mauvaise réponse. Bonne réponse : '+q.bonne});
    } else {
      const norm=iaNormaliser(rep);
      const trouves=(q.motsCles||[]).filter(m=>norm.includes(iaNormaliser(m)));
      const ratio = q.motsCles.length? trouves.length/q.motsCles.length : 0;
      let pts=0;
      if(rep.length<10) pts=0;
      else if(ratio>=0.6) pts=q.points;
      else if(ratio>=0.35) pts=Math.round(q.points*0.6);
      else if(ratio>0) pts=Math.round(q.points*0.3);
      // bonus longueur pertinente
      if(rep.length>60 && pts<q.points && ratio>=0.35) pts=Math.min(q.points, pts+1);
      total+=pts;
      const confiance = Math.round(55 + ratio*40 + Math.min(5, rep.length/60));
      details.push({qid:q.id, type:'ouverte', reponse:rep||'(sans réponse)', points:pts+'/'+q.points,
        motsTrouves:trouves, confiance:confiance+'%',
        commentaire: `Mots-clés détectés ${trouves.length}/${q.motsCles.length} : ${trouves.join(', ')||'aucun'}. Attendus : ${q.motsCles.join(', ')}.`});
    }
  });
  const note20 = Math.round(total/max*20);
  let appreciation = note20>=16?'Excellent travail, continuez.':note20>=12?'Bien, quelques notions à renforcer.':note20>=10?'Passable, relisez le support de cours.':'Insuffisant, reprenez le chapitre et la bibliothèque.';
  const feedback = `Correction automatique IA — ${total}/${max} → ${note20}/20.\n`+details.map(d=>`• ${d.qid} (${d.points}) : ${d.commentaire}`).join('\n')+`\nAppréciation : ${appreciation}\n⚠️ Proposition IA à valider par l'enseignant.`;
  return {noteIA:note20, total, max, details, feedback};
}
function iaSuggestions(livres, contexte){
  // contexte: {recherche, coursCategorie, historique[]}
  const norm=iaNormaliser((contexte.recherche||'')+' '+(contexte.coursCategorie||''));
  const scored = livres.map(l=>{
    let s=0;
    const hay=iaNormaliser(l.titre+' '+l.auteur+' '+l.categorie+' '+l.mots.join(' '));
    norm.split(/\s+/).forEach(w=>{ if(w.length>2 && hay.includes(w)) s+=3; });
    if(contexte.coursCategorie && l.categorie===contexte.coursCategorie) s+=4;
    (contexte.historique||[]).forEach(h=>{ if(iaNormaliser(l.categorie).includes(iaNormaliser(h))) s+=2; });
    s += Math.min(2, l.telechargements/200);
    return {l, s};
  }).sort((a,b)=>b.s-a.s);
  return scored.slice(0,3).map(x=>x.l);
}
/* Rapport de correction IA — HTML riche partagé (Résultats + file prof) */
function iaPts(d){ const p=(d.points||'0/0').split('/'); return {got:parseInt(p[0])||0, max:parseInt(p[1])||0}; }
function iaRapportHTML(x, ev){
  const qs = ev ? ev.questions : [];
  const note = (x.noteFinale ?? x.noteIA ?? 0);
  const m = (x.feedbackIA||'').match(/Appréciation\s*:\s*(.+)/);
  const appr = m ? m[1].trim() : '';
  const pct = Math.round(note/20*100);
  const ringC = x.statut==='validée' ? (note>=12?'#059669':note>=10?'#b45309':'#dc2626') : '#7c3aed';
  let body = '';
  if(x.detailsIA && x.detailsIA.length){
    body = '<div class="p-2">' + x.detailsIA.map((d,i)=>{
      const q = qs.find(q=>q.id===d.qid) || {};
      const title = q.enonce || d.qid;
      const pts = iaPts(d);
      const okQcm = d.type==='qcm' && pts.max>0 && pts.got===pts.max;
      let inner = '';
      if(d.type==='qcm'){
        inner = `<div class="mt-1"><span class="chip ${okQcm?'chip-ok':'chip-ko'}"><i class="bi ${okQcm?'bi-check-lg':'bi-x-lg'}"></i> Votre réponse : ${d.reponse||'—'}</span>`
          + (okQcm?'':` <span class="chip chip-ok"><i class="bi bi-flag"></i> Attendu : ${d.attendu}</span>`) + `</div>`
          + `<div class="small text-muted mt-1">${d.commentaire||''}</div>`;
      } else {
        const attendus = q.motsCles || [];
        const trouves = d.motsTrouves || [];
        const manq = attendus.filter(k=>!trouves.includes(k));
        const conf = parseInt(d.confiance)||0;
        inner = `<div class="fst-italic text-muted small mt-1">« ${d.reponse} »</div><div class="mt-1">`
          + trouves.map(t=>`<span class="chip chip-ok"><i class="bi bi-check-lg"></i> ${t}</span>`).join('')
          + manq.map(t=>`<span class="chip chip-ko"><i class="bi bi-dash"></i> ${t}</span>`).join('') + `</div>`
          + `<div class="d-flex align-items-center gap-2 mt-2"><small class="text-muted"><i class="bi bi-speedometer2"></i> Confiance IA ${d.confiance}</small><div class="conf-bar flex-fill"><div style="width:${conf}%"></div></div></div>`;
      }
      return `<div class="ia-q"><div class="d-flex align-items-center gap-2 flex-wrap">`
        + `<span class="exam-qnum">${i+1}</span><strong class="flex-fill">${title}</strong>`
        + `<span class="badge-${okQcm||pts.got>0?'uwb':'secondary'}">${d.points} pts</span>`
        + (d.type==='qcm'
          ? (okQcm?'<span class="badge-uwb"><i class="bi bi-check-circle-fill"></i> Juste</span>':'<span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> Faux</span>')
          : '<span class="badge-ia"><i class="bi bi-robot"></i> Analyse IA</span>')
        + `</div>${inner}</div>`;
    }).join('') + '</div>';
  } else {
    // Anciennes copies : parse le texte du feedback en lignes stylées
    const rows = (x.feedbackIA||'').split('\n').filter(l=>l.trim().startsWith('•'));
    body = '<div class="p-2">' + (rows.map(r=>`<div class="ia-q"><i class="bi bi-chevron-right text-muted"></i> ${r.slice(1).trim()}</div>`).join('') || `<div class="ia-q">${x.feedbackIA||'Pas de détail.'}</div>`) + '</div>';
  }
  const foot = x.statut==='validée'
    ? `<div class="ia-report-foot ok"><i class="bi bi-check-circle-fill"></i> Résultat validé par le professeur — note finale <strong>${x.noteFinale}/20</strong>${x.commentProf?' • « '+x.commentProf+' »':''}</div>`
    : `<div class="ia-report-foot"><i class="bi bi-exclamation-triangle-fill"></i> Proposition IA <strong>${x.noteIA}/20</strong> — en attente de validation par l'enseignant.</div>`;
  return `<div class="ia-report mt-2"><div class="ia-report-head">`
    + `<span class="note-ring" style="--p:${pct};--c:${ringC}"><span>${note}/20</span></span>`
    + `<div><strong><i class="bi bi-robot"></i> Rapport de correction IA</strong>${appr?`<br><small class="text-muted"><i class="bi bi-chat-quote"></i> ${appr}</small>`:''}</div>`
    + `<span class="ms-auto badge-ia"><i class="bi bi-cpu"></i> QCM 100% + mots-clés</span></div>`
    + body + foot + `</div>`;
}
