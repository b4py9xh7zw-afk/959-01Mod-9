<?php
$pageTitle = '许可证详情 - 许可证管理平台';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            许可证详情
        </h1>
        <a href="/dashboard/licenses" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
            ← 返回许可证列表
        </a>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">许可证密钥</label>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <code class="text-lg font-mono text-gray-800"><?php echo htmlspecialchars($license['license_key']); ?></code>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">状态</label>
                    <div class="mt-2">
                        <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full <?php 
                            echo $license['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                ($license['status'] === 'expired' ? 'bg-red-100 text-red-800' :
                                ($license['status'] === 'upgraded' ? 'bg-blue-100 text-blue-800' :
                                ($license['status'] === 'downgraded' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'))); 
                        ?>">
                            <?php 
                            echo $license['status'] === 'active' ? '活跃' : 
                                ($license['status'] === 'expired' ? '已过期' :
                                ($license['status'] === 'upgraded' ? '已升级' :
                                ($license['status'] === 'downgraded' ? '已回退' : '未激活'))); 
                            ?>
                        </span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">产品名称</label>
                    <p class="text-lg text-gray-800"><?php echo htmlspecialchars($license['product_name']); ?></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">分配用户</label>
                    <p class="text-lg text-gray-800"><?php echo htmlspecialchars($license['username'] ?? 'N/A'); ?></p>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($license['email'] ?? ''); ?></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">创建时间</label>
                    <p class="text-lg text-gray-800"><?php echo date('Y-m-d H:i:s', strtotime($license['created_at'])); ?></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">过期时间</label>
                    <p class="text-lg text-gray-800">
                        <?php echo $license['expires_at'] ? date('Y-m-d H:i:s', strtotime($license['expires_at'])) : '永不过期'; ?>
                    </p>
                </div>
                
                <?php if (!empty($license['order_id'])): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">关联订单</label>
                    <p class="text-lg text-gray-800">
                        <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-sm font-medium">
                            订单 #<?php echo htmlspecialchars($license['order_id']); ?>
                        </span>
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($license['upgrade_count'])): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">升级次数</label>
                    <p class="text-lg text-gray-800">
                        <span class="bg-purple-50 text-purple-700 px-3 py-1 rounded-full text-sm font-medium">
                            <?php echo $license['upgrade_count']; ?> 次
                        </span>
                        <?php if (!empty($license['last_upgraded_at'])): ?>
                            <span class="text-sm text-gray-500 ml-2">
                                最后升级: <?php echo date('Y-m-d', strtotime($license['last_upgraded_at'])); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="border-t border-gray-200 pt-6 mt-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">套餐操作</h3>
                <div class="flex flex-wrap gap-3">
                    <?php if ($license['status'] === 'active'): ?>
                        <a href="/licenses/upgrade/preview?license_id=<?php echo $license['id']; ?>" 
                           class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all transform hover:scale-105">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                升级套餐
                            </span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($license['status'] === 'active' && !empty($license['source_license_id'])): ?>
                        <form method="POST" action="/licenses/downgrade" onsubmit="return confirm('确定要回退套餐吗？回退后将恢复之前的许可证，所有历史记录将被保留。');" class="inline">
                            <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                            <button type="submit" class="px-6 py-3 bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600 transition-colors">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                    </svg>
                                    回退套餐
                                </span>
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <a href="/licenses/upgrade/history?license_id=<?php echo $license['id']; ?>" 
                       class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            查看历史
                        </span>
                    </a>
                </div>
            </div>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="border-t border-gray-200 pt-6 mt-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">管理员操作</h3>
                <div class="flex space-x-4">
                    <button 
                        onclick="document.getElementById('updateForm').classList.toggle('hidden')"
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                    >
                        编辑许可证
                    </button>
                    <form method="POST" action="/licenses/delete" onsubmit="return confirm('确定要删除此许可证吗？');" class="inline">
                        <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                        <button 
                            type="submit"
                            class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                        >
                            删除许可证
                        </button>
                    </form>
                </div>
                
                <form id="updateForm" method="POST" action="/licenses/update" class="hidden mt-6 space-y-4 bg-gray-50 p-6 rounded-lg">
                    <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                    
                    <div>
                        <label for="product_name" class="block text-sm font-medium text-gray-700 mb-2">产品名称</label>
                        <input 
                            type="text" 
                            id="product_name" 
                            name="product_name" 
                            value="<?php echo htmlspecialchars($license['product_name']); ?>"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">状态</label>
                        <select 
                            id="status" 
                            name="status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="active" <?php echo $license['status'] === 'active' ? 'selected' : ''; ?>>活跃</option>
                            <option value="inactive" <?php echo $license['status'] === 'inactive' ? 'selected' : ''; ?>>未激活</option>
                            <option value="expired" <?php echo $license['status'] === 'expired' ? 'selected' : ''; ?>>已过期</option>
                            <option value="upgraded" <?php echo $license['status'] === 'upgraded' ? 'selected' : ''; ?>>已升级</option>
                            <option value="downgraded" <?php echo $license['status'] === 'downgraded' ? 'selected' : ''; ?>>已回退</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">过期时间</label>
                        <input 
                            type="date" 
                            id="expires_at" 
                            name="expires_at"
                            value="<?php echo $license['expires_at'] ? date('Y-m-d', strtotime($license['expires_at'])) : ''; ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    
                    <div class="flex space-x-4">
                        <button 
                            type="submit"
                            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                        >
                            更新许可证
                        </button>
                        <button 
                            type="button"
                            onclick="document.getElementById('updateForm').classList.add('hidden')"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                        >
                            取消
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
