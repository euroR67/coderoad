var currentItemId;

function showSidebar(type, id, title, description, github, isOwner) {
  var sidebar = document.getElementById("sidebar");
  sidebar.classList.add("sidebar-active");

  // Ajouter les informations de l'élément
  document.getElementById("item-title").innerText = title;
  document.getElementById("item-type").innerText =
    type === "challenge" ? "Challenge" : "Projet";
  document.getElementById("item-description").innerText = description;

  document.getElementById("github-link").href = github || "#";
  document.getElementById("github-link").innerText = github || "";

  // Ajouter l'URL GitHub dans le champ du formulaire
  document.getElementById("github-url").value = github || "";

  // Afficher ou masquer le formulaire selon la propriété de l'utilisateur
  var githubForm = document.getElementById("github-form");

  if (isOwner == false) {
    githubForm.style.display = "none";
  } else {
    githubForm.style.display = "block";
  }

  currentItemId = id;
}

function closeSidebar() {
  // Fermer la sidebar
  var sidebar = document.getElementById("sidebar");
  sidebar.classList.remove("sidebar-active");
}

document.addEventListener("DOMContentLoaded", () => {
  // Sélectionne toutes les cartes
  const cards = document.querySelectorAll(".task");

  // Ajoute un écouteur de clic sur chaque carte
  cards.forEach((card) => {
    card.addEventListener("click", () => {
      cards.forEach((card) => {
        card.classList.remove("active");
      });
      card.classList.add("active");
    });
  });
});

function submitGitHubForm(event) {
  // Gérer la soumission du formulaire
  event.preventDefault(); // Empêcher le rechargement de la page

  var githubUrl = document.getElementById("github-url").value;
  var itemType = document.getElementById("item-type").innerText.toLowerCase(); // Récupérer le type (challenge ou project)
  var url = `/${itemType}/${currentItemId}/github`;

  // Logique pour enregistrer l'URL GitHub (ex: appel à une API)
  console.log("itemType :", itemType);
  console.log("url :", url);
  console.log("Nouvelle URL GitHub :", githubUrl);

  // Exemple de requête POST pour enregistrer l'URL GitHub (en utilisant Fetch API)
  fetch(url, {
    method: "PUT",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      github: githubUrl,
      // Ajoute d'autres informations si nécessaire, comme l'ID de l'élément
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showSuccessMessage(data.message);
      } else {
        showErrorMessage(data.message);
      }
      console.log("Succès:", data);
      // Fermer la sidebar après mise à jour
      closeSidebar();
    })
    .catch((error) => {
      showErrorMessage("Erreur lors de la mise à jour de l'URL GitHub");
      console.error("Erreur:", error);
    });
}
