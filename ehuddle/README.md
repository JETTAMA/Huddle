# 🎨 E-Huddle

E-Huddle is a vibrant, "cartoon-style" social media platform designed for a playful and engaging user experience. Built with a focus on a "neubrutalist" aesthetic, it features bold borders, popping colors, and interactive micro-animations.

## 🚀 Features

- **Interactive Feed:** Share your thoughts and images with a playful UI.
- **Image Uploads:** Seamless image uploads for posts, avatars, and profile banners.
- **Social Interactions:** Like, comment, and follow other "huddlers."
- **Search:** Real-time search for users and posts.
- **Responsive Design:** Fully responsive layout with a dedicated mobile menu.
- **Cartoon Aesthetic:** Custom animations (wobble, pop, bounce) and bold shadow-driven UI components.
- **Secure:** Military-grade security measures in place

### Local Development

1.  Clone the repository.
2.  Start a local PHP server in the project root:
    ```bash
    php -S localhost:8000 router.php
    ```
3.  Open `http://localhost:8000` in your browser.
4.  The database will be automatically initialized and seeded on the first run.

### Docker / Production Notes

- The frontend no longer relies on hardcoded `localhost` for API calls.
- By default, API requests use same-origin paths like `/api/...` (best when using a reverse proxy).
- If frontend and backend are on different hosts, set `EH_API_BASE_URL`:
  ```bash
  EH_API_BASE_URL=https://api.example.com docker compose up -d --build
  ```

























































## 📜 AI Agent Note

> [!IMPORTANT]
> For AI agents working on this codebase: **always check `.AGENTS.md`** for specific instructions, architectural guidelines, and project-specific constraints and security information before making any code changes or suggestions. This file contains critical information about the project's design and security considerations that must be followed to maintain the integrity of the codebase.


























