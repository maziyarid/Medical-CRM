"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { posts } from "../site-data";
import { Icon } from "./icons";

const categories = ["همه", ...Array.from(new Set(posts.map((post) => post.category)))];

export function BlogExplorer() {
  const [category, setCategory] = useState("همه");
  const [query, setQuery] = useState("");

  const visible = useMemo(() => {
    const normalized = query.trim().toLocaleLowerCase("fa");
    return posts.filter((post) => {
      const categoryMatch = category === "همه" || post.category === category;
      const queryMatch =
        !normalized ||
        `${post.title} ${post.excerpt}`
          .toLocaleLowerCase("fa")
          .includes(normalized);
      return categoryMatch && queryMatch;
    });
  }, [category, query]);

  return (
    <>
      <div className="blog-tools">
        <div className="category-tabs" role="group" aria-label="فیلتر موضوع مقاله">
          {categories.map((item) => (
            <button
              type="button"
              key={item}
              onClick={() => setCategory(item)}
              className={category === item ? "active" : ""}
            >
              {item}
            </button>
          ))}
        </div>
        <label className="blog-search">
          <span className="sr-only">جست‌وجوی مقاله</span>
          <Icon name="search" size={19} />
          <input
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="جست‌وجو در مقاله‌ها..."
          />
        </label>
      </div>

      {visible.length ? (
        <div className="post-grid post-grid-wide">
          {visible.map((post) => (
            <article className="post-card" key={post.slug} id={post.slug}>
              <Link href={`/blog#${post.slug}`} className="post-image">
                <img src={post.image} alt="" />
              </Link>
              <div className="post-content">
                <div className="post-meta">
                  <span>{post.category}</span>
                  <span>{post.readTime} مطالعه</span>
                </div>
                <h2>
                  <Link href={`/blog#${post.slug}`}>{post.title}</Link>
                </h2>
                <p>{post.excerpt}</p>
                <Link href={`/blog#${post.slug}`} className="text-link">
                  مشاهده خلاصه
                  <Icon name="arrow" size={16} />
                </Link>
              </div>
            </article>
          ))}
        </div>
      ) : (
        <div className="empty-state">
          <Icon name="search" size={32} />
          <h2>مقاله‌ای پیدا نشد</h2>
          <p>عبارت دیگری جست‌وجو کنید یا فیلتر موضوع را تغییر دهید.</p>
          <button
            type="button"
            className="button button-outline"
            onClick={() => {
              setQuery("");
              setCategory("همه");
            }}
          >
            نمایش همه مقاله‌ها
          </button>
        </div>
      )}
    </>
  );
}
