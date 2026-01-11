document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector(".site-header");
  const navToggle = document.querySelector(".nav-toggle");
  const navLinks = document.querySelectorAll(".site-nav a");
  const body = document.body;

  if (navToggle) {
    navToggle.addEventListener("click", () => {
      const expanded = navToggle.getAttribute("aria-expanded") === "true";
      navToggle.setAttribute("aria-expanded", String(!expanded));
      header?.classList.toggle("nav-open", !expanded);
      body.classList.toggle("lock-scroll", !expanded);
    });

    navLinks.forEach((link) =>
      link.addEventListener("click", () => {
        navToggle.setAttribute("aria-expanded", "false");
        header?.classList.remove("nav-open");
        body.classList.remove("lock-scroll");
      })
    );
  }

  document.querySelectorAll("[data-scroll-to]").forEach((trigger) => {
    trigger.addEventListener("click", (event) => {
      const targetId = trigger.getAttribute("data-scroll-to");
      const targetEl = targetId ? document.querySelector(targetId) : null;
      if (targetEl) {
        event.preventDefault();
        targetEl.scrollIntoView({ behavior: "smooth" });
      }
    });
  });

  document.querySelectorAll("[data-countdown]").forEach((countdown) => {
    const target = countdown.getAttribute("data-countdown-target");
    if (!target) {
      return;
    }

    const targetDate = new Date(target);
    if (Number.isNaN(targetDate.getTime())) {
      return;
    }

    const daysNode = countdown.querySelector("[data-countdown-days]");
    const hoursNode = countdown.querySelector("[data-countdown-hours]");
    const minutesNode = countdown.querySelector("[data-countdown-minutes]");
    const secondsNode = countdown.querySelector("[data-countdown-seconds]");

    if (!daysNode || !hoursNode || !minutesNode || !secondsNode) {
      return;
    }

    let intervalId = null;

    const stop = () => {
      if (intervalId !== null) {
        window.clearInterval(intervalId);
        intervalId = null;
      }
    };

    const updateValues = () => {
      const now = new Date();
      const diff = targetDate.getTime() - now.getTime();

      if (diff <= 0) {
        countdown.classList.add("countdown--complete");
        daysNode.textContent = "00";
        hoursNode.textContent = "00";
        minutesNode.textContent = "00";
        secondsNode.textContent = "00";
        return false;
      }

      const totalSeconds = Math.floor(diff / 1000);
      const days = Math.floor(totalSeconds / 86400);
      const hours = Math.floor((totalSeconds % 86400) / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;

      const format = (value) => String(value).padStart(2, "0");

      daysNode.textContent = format(days);
      hoursNode.textContent = format(hours);
      minutesNode.textContent = format(minutes);
      secondsNode.textContent = format(seconds);

      return true;
    };

    const initialised = updateValues();
    if (!initialised) {
      countdown.classList.add("countdown--complete");
      return;
    }

    intervalId = window.setInterval(() => {
      const hasTimeRemaining = updateValues();
      if (!hasTimeRemaining) {
        stop();
      }
    }, 1000);

    window.addEventListener("beforeunload", stop);
  });

  const voteForm = document.querySelector(".vote-form");
  if (voteForm) {
    const trackFilter = voteForm.querySelector("[data-track-filter]");
    const trackList = voteForm.querySelector("[data-track-list]");
    const feedback = voteForm.querySelector("[data-selection-feedback]");
    const submitButton = voteForm.querySelector('button[type="submit"]');
    const throttleNotice = document.createElement("p");
    throttleNotice.className = "selection-feedback throttle-notice";

    if (trackList) {
      const items = Array.from(trackList.querySelectorAll("[data-track-item]"));
      const checkboxes = items
        .map((item) => item.querySelector('input[type="checkbox"]'))
        .filter((checkbox) => checkbox);
      const maxSelections = Number(trackList.getAttribute("data-max")) || 3;

      const setFeedback = (message) => {
        if (!feedback) return;
        feedback.textContent = message;
      };

      const updateSelectionState = () => {
        const checkedCount = checkboxes.filter(
          (checkbox) => checkbox.checked
        ).length;
        if (checkedCount === 0) {
          setFeedback("");
        } else {
          setFeedback(`Geselecteerd: ${checkedCount}/${maxSelections}.`);
        }
      };

      checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", (event) => {
          const currentCheckbox = event.target;
          if (!(currentCheckbox instanceof HTMLInputElement)) {
            return;
          }
          const parentItem = currentCheckbox.closest("[data-track-item]");
          const checkedCount = checkboxes.filter((cb) => cb.checked).length;

          if (checkedCount > maxSelections) {
            currentCheckbox.checked = false;
            if (parentItem) {
              parentItem.classList.remove("is-checked");
            }
            setFeedback(`Je mag maximaal ${maxSelections} nummers kiezen.`);
            currentCheckbox.focus();
            return;
          }

          if (parentItem) {
            parentItem.classList.toggle("is-checked", currentCheckbox.checked);
          }
          updateSelectionState();
        });

        const parentItem = checkbox.closest("[data-track-item]");
        if (parentItem) {
          parentItem.classList.toggle("is-checked", checkbox.checked);
        }
      });

      updateSelectionState();

      if (trackFilter) {
        const filterItems = (query) => {
          const normalized = query.trim().toLowerCase();
          items.forEach((item) => {
            const searchText = item.dataset.search ?? "";
            const matches =
              normalized === "" || searchText.includes(normalized);
            item.classList.toggle("is-hidden", !matches);
          });
        };

        trackFilter.addEventListener("input", (event) => {
          filterItems(event.target.value);
        });

        filterItems(trackFilter.value || "");
      }
    }

    const localStorageAvailable = (() => {
      try {
        const key = "v75_test";
        window.localStorage.setItem(key, "1");
        window.localStorage.removeItem(key);
        return true;
      } catch (_) {
        return false;
      }
    })();

    const throttleKey = "v75_vote_last_sent";
    const throttleSeconds = 24 * 60 * 60; // 24h block per device
    const cooldownSeconds = 5 * 60; // 5 min cooldown for rapid retries

    const disableForm = (message) => {
      if (submitButton) {
        submitButton.disabled = true;
      }
      voteForm.querySelectorAll("input, button").forEach((el) => {
        if (el instanceof HTMLButtonElement || el instanceof HTMLInputElement) {
          el.disabled = true;
        }
      });
      throttleNotice.textContent = message;
      if (!throttleNotice.isConnected) {
        voteForm.appendChild(throttleNotice);
      }
    };

    const checkThrottle = () => {
      if (!localStorageAvailable) return false;

      const raw = window.localStorage.getItem(throttleKey);
      if (!raw) return false;

      const last = Number.parseInt(raw, 10);
      if (!Number.isFinite(last) || last <= 0) return false;

      const now = Math.floor(Date.now() / 1000);
      const diff = now - last;
      const remainingCooldown = cooldownSeconds - diff;
      const remainingWindow = throttleSeconds - diff;
      const remaining = Math.max(remainingCooldown, remainingWindow);

      if (remaining > 0) {
        const minutes = Math.max(1, Math.ceil(remaining / 60));
        disableForm(
          `Je hebt net gestemd vanaf dit apparaat. Wacht nog ongeveer ${minutes} minuut${
            minutes === 1 ? "" : "en"
          } voordat je het opnieuw probeert.`
        );
        return true;
      }

      return false;
    };

    const markSent = () => {
      if (!localStorageAvailable) return;
      const now = Math.floor(Date.now() / 1000);
      window.localStorage.setItem(throttleKey, String(now));
    };

    checkThrottle();

    voteForm.addEventListener("submit", () => {
      markSent();
    });
  }

  const teaserPlayers = document.querySelectorAll("[data-teaser-player]");
  teaserPlayers.forEach((player) => {
    const video = player.querySelector("video");
    const overlay = player.querySelector("[data-teaser-overlay]");
    const label = player.querySelector("[data-teaser-label]");

    if (!video || !overlay || !label) {
      return;
    }

    let countdownTimer = null;
    let state = "idle";

    const clearTimer = () => {
      if (countdownTimer) {
        window.clearInterval(countdownTimer);
        countdownTimer = null;
      }
    };

    const showOverlay = (message) => {
      clearTimer();
      state = "idle";
      overlay.classList.remove("is-hidden", "is-counting");
      overlay.setAttribute("aria-hidden", "false");
      label.textContent = message;
    };

    overlay.addEventListener("click", () => {
      if (state === "counting") {
        return;
      }

      state = "counting";
      overlay.classList.add("is-counting");

      let remaining = 5;
      label.textContent = String(remaining);

      countdownTimer = window.setInterval(() => {
        remaining -= 1;

        if (remaining >= 0) {
          label.textContent = String(remaining);
        }

        if (remaining < 0) {
          clearTimer();
          state = "playing";
          overlay.classList.add("is-hidden");
          overlay.setAttribute("aria-hidden", "true");

          video.play().catch(() => {
            showOverlay("Klik om de teaser te starten");
          });
        }
      }, 1000);
    });

    video.addEventListener("ended", () => {
      showOverlay("Klik om de teaser opnieuw te starten");
      video.currentTime = 0;
    });

    video.addEventListener("pause", () => {
      if (video.ended || state !== "playing") {
        return;
      }

      showOverlay("Klik om verder te gaan");
    });

    window.addEventListener("beforeunload", clearTimer);
  });
});
