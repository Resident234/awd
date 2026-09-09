$.sidebarMenu = function (menu) {
  var animationSpeed = 300;

  $(menu).on("click", "li a", function (e) {
    var $this = $(this);
    var checkElement = $this.next();

    if (checkElement.is(".treeview-menu") && checkElement.is(":visible")) {
      checkElement.slideUp(animationSpeed, function () {
        checkElement.removeClass("menu-open");
      });
      checkElement.parent("li").removeClass("active");
    }

    //If the menu is not visible
    else if (
      checkElement.is(".treeview-menu") &&
      !checkElement.is(":visible")
    ) {
      //Get the parent menu
      var parent = $this.parents("ul").first();
      //Close all open menus within the parent
      var ul = parent.find("ul:visible").slideUp(animationSpeed);
      //Remove the menu-open class from the parent
      ul.removeClass("menu-open");
      //Get the parent li
      var parent_li = $this.parent("li");

      //Open the target menu and add the menu-open class
      checkElement.slideDown(animationSpeed, function () {
        //Add the class active to the parent li
        checkElement.addClass("menu-open");
        parent.find("li.active").removeClass("active");
        parent_li.addClass("active");
      });
    }
    //if this isn't a link, prevent the page from being redirected
    if (checkElement.is(".treeview-menu")) {
      e.preventDefault();
    }
  });
};
$.sidebarMenu($(".sidebar-menu"));

// Custom Sidebar JS
jQuery(function ($) {
  //toggle sidebar
  $("#toggle-sidebar").on("click", function () {
    $(".page-wrapper").toggleClass("toggled");
  });

  // Pin sidebar on click
  $("#pin-sidebar").on("click", function () {
    const $icon = $(this).find("i");

    if ($(".page-wrapper").hasClass("pinned")) {
      // Unpin sidebar
      $(".page-wrapper").removeClass("pinned");
      $("#sidebar").unbind("hover");

      // Change icon back to default
      $icon.removeClass("bi-layout-sidebar-reverse").addClass("bi-layout-sidebar");
    } else {
      // Pin sidebar
      $(".page-wrapper").addClass("pinned");
      $("#sidebar").hover(
        function () {
          console.log("mouseenter");
          $(".page-wrapper").addClass("sidebar-hovered");
        },
        function () {
          console.log("mouseout");
          $(".page-wrapper").removeClass("sidebar-hovered");
        }
      );

      // Change icon to reverse
      $icon.removeClass("bi-layout-sidebar").addClass("bi-layout-sidebar-reverse");
    }
  });


  // Pinned sidebar
  $(function () {
    $(".page-wrapper").hasClass("pinned");
    $("#sidebar").hover(
      function () {
        console.log("mouseenter");
        $(".page-wrapper").addClass("sidebar-hovered");
      },
      function () {
        console.log("mouseout");
        $(".page-wrapper").removeClass("sidebar-hovered");
      }
    );
  });

  // Toggle sidebar overlay
  $("#overlay").on("click", function () {
    $(".page-wrapper").toggleClass("toggled");
  });

  // Added by Srinu
  $(function () {
    // When the window is resized,
    $(window).resize(function () {
      // When the width and height meet your specific requirements or lower
      if ($(window).width() <= 768) {
        $(".page-wrapper").removeClass("pinned");
      }
    });
    // When the window is resized,
    $(window).resize(function () {
      // When the width and height meet your specific requirements or lower
      if ($(window).width() >= 768) {
        $(".page-wrapper").removeClass("toggled");
      }
    });
  });
});

// Subscribers Page Toggle Icon
document.addEventListener('click', function (event) {
  if (event.target.closest('.subscribe-user')) {
    event.preventDefault(); // Prevent the default action of the anchor tag
    var icon = event.target.closest('.subscribe-user').querySelector('i');
    icon.classList.toggle('text-danger');
    icon.classList.toggle('text-light');
  }
});

// Sticky Header
const header = document.querySelector('.app-header');
function updateStickyHeader() {
  if (window.scrollY > 10) {
    header.classList.add('sticky-header');
  } else {
    header.classList.remove('sticky-header');
  }
}
window.addEventListener('scroll', updateStickyHeader);
// Apply on page load
updateStickyHeader();

/***********
***********
***********
  Bootstrap JS 
***********
***********
***********/

// Tooltip
var tooltipTriggerList = [].slice.call(
  document.querySelectorAll('[data-bs-toggle="tooltip"]')
);
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Popover
var popoverTriggerList = [].slice.call(
  document.querySelectorAll('[data-bs-toggle="popover"]')
);
var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
  return new bootstrap.Popover(popoverTriggerEl);
});

// Toasts
document.addEventListener('DOMContentLoaded', function () {
  // Helper function to set up toast button listeners
  function setupToastButton(btnId, toastId) {
    const btn = document.getElementById(btnId);
    if (btn) {
      btn.addEventListener('click', function () {
        const toastElement = document.getElementById(toastId);
        if (toastElement) {
          const toast = new bootstrap.Toast(toastElement);
          toast.show();
        }
      });
    }
  }

  // Basic Toast
  setupToastButton('liveToastBtn', 'liveToast');

  // Placement Toasts
  setupToastButton('topLeftToastBtn', 'topLeftToast');
  setupToastButton('topRightToastBtn', 'topRightToast');
  setupToastButton('bottomLeftToastBtn', 'bottomLeftToast');
  setupToastButton('bottomRightToastBtn', 'bottomRightToast');

  // Colored Toasts
  setupToastButton('primaryToastBtn', 'primaryToast');
  setupToastButton('successToastBtn', 'successToast');
  setupToastButton('dangerToastBtn', 'dangerToast');
  setupToastButton('warningToastBtn', 'warningToast');
  setupToastButton('infoToastBtn', 'infoToast');

  // Icon Toasts
  setupToastButton('infoIconToastBtn', 'infoIconToast');
  setupToastButton('successIconToastBtn', 'successIconToast');
  setupToastButton('errorIconToastBtn', 'errorIconToast');
  setupToastButton('warningIconToastBtn', 'warningIconToast');
});