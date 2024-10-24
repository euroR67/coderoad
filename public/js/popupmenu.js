function togglePopupMenu(event, id) {
  event.stopPropagation();

  // Fermer tous les autres menus
  document.querySelectorAll(".popup-menu").forEach((menu) => {
    menu.classList.remove("active");
  });

  // Ouvrir le popup pour l'élément cliqué
  var menu = document.getElementById("popup-menu-" + id);
  menu.classList.toggle("active"); // Afficher ou cacher le menu
}

// Fonction pour mettre à jour le statut de l'élément
function updateStatus(event, id, type, status, statusStr) {
  event.stopPropagation();
  var url = `/${type}/${id}/status`;

  // Fermer le popup menu
  var menu = document.getElementById("popup-menu-" + id);
  menu.classList.remove("active");

  // Envoyer la nouvelle valeur du statut au backend (exemple de requête)
  fetch(url, {
    method: "PUT",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ status: status }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Mettre à jour l'affichage visuel du statut
        const statusElement = document
          .querySelector(`#popup-menu-${id}`)
          .closest(".task")
          .querySelector(".status");
        statusElement.innerText = statusStr; // Mettre à jour le texte du statut
        // change la couleur du statut du parent
        const parent = statusElement.closest(".task__header");

        // supprimer la classe de couleur actuelle
        parent.classList.remove("task__todo");
        parent.classList.remove("task__inprogress");
        parent.classList.remove("task__done");
        console.log("parent", parent);

        // ajouter la nouvelle classe de couleur
        if (status === 1) {
          parent.classList.add("task__todo");
        } else if (status === 2) {
          parent.classList.add("task__inprogress");
        } else if (status === 3) {
          parent.classList.add("task__done");
        }
      } else {
        showErrorMessage(data.message);
      }
    })
    .catch((error) => {
      showErrorMessage("Erreur lors de la mise à jour de l'URL GitHub");

      console.error("Erreur:", error);
    });
}

// Fermer les popup menus si on clique en dehors
document.addEventListener("click", function () {
  document.querySelectorAll(".popup-menu").forEach((menu) => {
    menu.classList.remove("active");
  });
});
